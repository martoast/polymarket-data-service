# Recorder → Data Service Integration Plan

---

## Architecture Overview

```
┌─────────────────────────────────────────────┐
│              market-recorder                │
│  (Node.js/TypeScript, always-on server)     │
│                                             │
│  Chainlink WS ──► CryptoPriceFeed           │
│  Polymarket WS ──► MarketSubscriber         │
│                        │                   │
│                   EventBus                  │
│                   /       \                 │
│          MarketRecorder  SqliteWriter ◄─NEW │
│          (JSONL files)   (SQLite DB)        │
└───────────────────────────┬─────────────────┘
                            │
                   SQLite file on disk
                   (WAL mode, raw events)
                            │
                    ┌───────▼────────┐
                    │  Sync Process  │  ◄── runs every 1–5 min
                    │  (rsync / API  │       via cron or artisan
                    │   push)        │
                    └───────┬────────┘
                            │
┌───────────────────────────▼─────────────────┐
│            polymarket-data-service           │
│         (Laravel, Fly.io / VPS)             │
│                                             │
│  Ingest Worker ──► PostgreSQL + TimescaleDB │
│  Resolution Worker (Gamma API polls)        │
│  Feature Worker (window_features compute)   │
│                                             │
│  REST API ──► subscribers                   │
└─────────────────────────────────────────────┘
```

**Two databases, two roles:**

- **SQLite** on the recording machine — raw event store. Every tick, every CLOB update.
  Append-only. Simple schema. The recorder owns this entirely.

- **PostgreSQL** on the API server — enriched, queryable store. Resolved outcomes,
  computed features, joined tables. The Laravel service owns this entirely.

The recorder never touches PostgreSQL. The Laravel service never touches SQLite directly —
it reads synced data via the ingest pipeline.

---

## Why SQLite as the Bridge

- Zero network dependency in the write path — recording never fails because the API server is down
- WAL (Write-Ahead Logging) mode handles high-frequency writes + concurrent reads cleanly
- Portable — the SQLite file itself is the export product (Pro tier: download this file)
- Matches existing JSONL discipline: append-only, no updates, easy to tail

---

## Recorder Changes Required

### New file: `server/recorder/sqlite-writer.ts`

A new class parallel to `MarketRecorder`. It subscribes to the same EventBus events and writes
to SQLite instead of JSONL files.

**Add dependency:** `better-sqlite3` (synchronous SQLite for Node.js — safe with WAL mode)

```typescript
import Database from 'better-sqlite3'
import { eventBus } from '../engine/event-bus'

export class SqliteWriter {
  private db: Database.Database
  private stmts: Record<string, Database.Statement>

  constructor(dbPath: string) {
    this.db = new Database(dbPath)
    this.db.pragma('journal_mode = WAL')     // enables concurrent read+write
    this.db.pragma('synchronous = NORMAL')   // safe with WAL, faster than FULL
    this.db.pragma('cache_size = -64000')    // 64MB cache
    this.initSchema()
    this.prepareStatements()
    this.registerListeners()
  }
  // ...
}
```

**Register in `server/plugins/recorder-engine.ts`** alongside the existing `MarketRecorder`:

```typescript
import { SqliteWriter } from '../recorder/sqlite-writer'

const sqliteWriter = new SqliteWriter(
  process.env.SQLITE_DB_PATH || 'data/polymarket.db'
)
```

**New env vars for recorder (.env):**

```bash
SQLITE_DB_PATH=data/polymarket.db     # path to the SQLite file
SQLITE_ENABLED=true                   # toggle off for dev without DB
```

---

## SQLite Schema (Raw Event Store)

This is the recorder's schema — deliberately simpler than the full PostgreSQL schema.
No computed features, no resolution outcomes, no enrichment. Raw events only.

```sql
-- Markets (populated from Gamma API market discovery)
CREATE TABLE IF NOT EXISTS markets (
    condition_id    TEXT PRIMARY KEY,           -- hex conditionId from Polymarket
    asset           TEXT NOT NULL,              -- BTC | ETH | SOL
    slug            TEXT,                       -- btc-updown-5m-1773136800
    question        TEXT,                       -- human-readable title
    break_price     REAL,                       -- oracle price at window open
    open_ts         INTEGER NOT NULL,           -- UTC ms
    close_ts        INTEGER NOT NULL,           -- UTC ms
    duration_sec    INTEGER,                    -- 300 or 900
    resolved_ts     INTEGER,                    -- UTC ms, filled on resolution
    winner          TEXT,                       -- YES | NO (recorder-inferred — unreliable)
    discovered_at   INTEGER NOT NULL            -- when we first saw this market
);

CREATE INDEX IF NOT EXISTS idx_markets_asset_open ON markets (asset, open_ts DESC);
CREATE INDEX IF NOT EXISTS idx_markets_close ON markets (close_ts);

-- Oracle price ticks (crypto:trade events from Chainlink)
CREATE TABLE IF NOT EXISTS oracle_ticks (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    asset           TEXT NOT NULL,              -- BTC | ETH | SOL
    price           REAL NOT NULL,              -- USD price
    ts              INTEGER NOT NULL            -- UTC ms
);

CREATE INDEX IF NOT EXISTS idx_oracle_asset_ts ON oracle_ticks (asset, ts DESC);

-- CLOB price snapshots (price:update events)
CREATE TABLE IF NOT EXISTS clob_snapshots (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    condition_id    TEXT NOT NULL,              -- FK → markets.condition_id
    yes_bid         REAL,
    yes_ask         REAL,
    no_bid          REAL,
    no_ask          REAL,
    ts              INTEGER NOT NULL            -- UTC ms
);

CREATE INDEX IF NOT EXISTS idx_clob_market_ts ON clob_snapshots (condition_id, ts DESC);

-- 1-minute OHLCV candles (crypto:candle events, tf=1m only)
CREATE TABLE IF NOT EXISTS candles_1m (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    asset           TEXT NOT NULL,
    open            REAL NOT NULL,
    high            REAL NOT NULL,
    low             REAL NOT NULL,
    close           REAL NOT NULL,
    volume          REAL,
    ts              INTEGER NOT NULL            -- candle open time, UTC ms
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_candles_asset_ts ON candles_1m (asset, ts);

-- Oracle settlements at window boundaries (oracle:settle events)
CREATE TABLE IF NOT EXISTS oracle_settlements (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    asset           TEXT NOT NULL,
    timeframe       TEXT NOT NULL,              -- 5m | 15m
    window_start    INTEGER NOT NULL,           -- UTC ms
    settlement_price REAL NOT NULL,
    ts              INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_settle_asset_ts ON oracle_settlements (asset, ts DESC);

-- Ingest cursor: tracks how far the Laravel service has consumed each table
CREATE TABLE IF NOT EXISTS sync_cursors (
    table_name      TEXT PRIMARY KEY,
    last_id         INTEGER NOT NULL DEFAULT 0,
    last_synced_at  INTEGER
);

INSERT OR IGNORE INTO sync_cursors (table_name, last_id) VALUES
    ('oracle_ticks', 0),
    ('clob_snapshots', 0),
    ('candles_1m', 0),
    ('oracle_settlements', 0),
    ('markets', 0);
```

---

## Event → SQLite Mapping

| Recorder event | SQLite table | Key fields written |
|---|---|---|
| `crypto:trade` | `oracle_ticks` | asset, price, ts |
| `price:update` | `clob_snapshots` | condition_id (marketId), yes_bid, yes_ask, no_bid, no_ask, ts |
| `crypto:candle` (tf=1m) | `candles_1m` | asset, o/h/l/cl/v, ts |
| `oracle:settle` | `oracle_settlements` | asset, timeframe, windowStart, settlementPrice, ts |
| `market:resolved` | `markets` (UPDATE) | resolved_ts, winner (condition_id lookup) |
| Market discovery | `markets` (INSERT OR IGNORE) | condition_id, asset, slug, open_ts, close_ts, break_price |

**Market discovery hook:** The `PriceEngine` in the recorder calls `MarketDiscovery` which fetches
from the Gamma API. We need to intercept the discovered markets and write them to SQLite.
This requires a small addition to `price-engine.ts` to emit a `market:discovered` event on the
EventBus, which `SqliteWriter` listens to.

**Write pattern (use prepared statements + batch for high-frequency events):**

```typescript
// oracle_ticks: ~1/second/asset — write immediately, no batching needed
eventBus.on('crypto:trade', ({ symbol, price, timestamp }) => {
  this.stmts.insertOracleTick.run(
    symbol.split('/')[0].toUpperCase(),  // "btc/usd" → "BTC"
    price,
    timestamp
  )
})

// clob_snapshots: 5+/second — batch into 100-row transactions
private clobBatch: object[] = []
eventBus.on('price:update', (data) => {
  this.clobBatch.push(data)
  if (this.clobBatch.length >= 100) this.flushClobBatch()
})
// Also flush every 1 second via setInterval
```

**SQLite WAL mode handles this volume comfortably** — 1,000+ inserts/second is normal.

---

## Sync Pipeline: SQLite → PostgreSQL

The Laravel service periodically pulls new rows from the SQLite file and runs them through
the ingest pipeline into PostgreSQL.

### Option A: Cursor-based pull (recommended for MVP)

The `sync_cursors` table in SQLite tracks the last row ID consumed per table.
The Laravel `ingest:sync` command:

1. Opens SQLite (read-only)
2. Reads `sync_cursors` to find last consumed `id` per table
3. Queries each table for rows with `id > last_id`
4. Batch-inserts into PostgreSQL via the existing ingest pipeline
5. Updates `sync_cursors.last_id` in SQLite on success

```bash
# Run this via cron on the API server, or from a scheduled artisan command
php artisan ingest:sync --db=/path/to/polymarket.db
```

**Frequency:** Every 1–5 minutes for near-real-time. Every hour for MVP.

### Option B: JSONL file sync (fallback / historical backfill)

Keep using rsync to copy JSONL files from the recording server to the API server,
then run the existing JSONL ingest pipeline. Works for historical backfill.
Can run alongside Option A.

```bash
# Cron on API server (or recording server pushing to API server)
rsync -avz --ignore-existing \
  recorder:/app/data/recordings/ \
  /var/data/recordings/
php artisan ingest:directory /var/data/recordings/
```

### Option C: Direct PostgreSQL writes from recorder (not recommended for MVP)

The recorder could write directly to the Fly.io PostgreSQL over the network.
Simple, but creates a hard network dependency in the write path — if the API server
is down or slow, recording stalls or drops events.

**Verdict: Use Option A for live data, Option B for historical backfill.**

---

## Deployment Layout

```
Recording Machine (always-on VPS or local server)
├── market-recorder (Node.js)
│   ├── writes JSONL to data/recordings/YYYY-MM-DD/
│   └── writes SQLite to data/polymarket.db (WAL mode)
└── cron: push SQLite to API server every 5 minutes
    rsync -avz data/polymarket.db api-server:/var/data/polymarket.db

API Server (Fly.io)
├── polymarket-data-service (Laravel)
│   └── cron: php artisan ingest:sync --db=/var/data/polymarket.db
│           runs every 5 minutes via Supervisor scheduler
└── PostgreSQL + TimescaleDB
    └── fully enriched data (features, resolutions, etc.)
```

**Alternative for local dev:** Both recorder and API on the same machine — point
`SQLITE_DB_PATH` and `ingest:sync --db` to the same file. No rsync needed.

---

## Data Flow: A Single Oracle Tick

```
Chainlink WS emits price update
        │
CryptoPriceFeed parses → emits crypto:trade
        │
        ├── MarketRecorder.on('crypto:trade')
        │       → append to oracle.jsonl
        │
        └── SqliteWriter.on('crypto:trade')             ← NEW
                → INSERT INTO oracle_ticks (...)
                   (prepared statement, WAL write)
                        │
                   [every 5 min] rsync polymarket.db → API server
                        │
                   php artisan ingest:sync
                        │
                   SELECT * FROM oracle_ticks WHERE id > last_id
                        │
                   batch INSERT INTO oracle_ticks (PostgreSQL/TimescaleDB)
                        │
                   GET /v1/oracle/ticks now returns this data
```

---

## Event Type Mismatch: Recorder vs SCHEMA.md

SCHEMA.md was written assuming BonereaderBot's event format (`e: "O"`, `e: "W"`, etc.).
The actual market-recorder uses different event names. The mapping:

| SCHEMA.md event | Recorder event | Notes |
|---|---|---|
| `O` oracle tick | `crypto:trade` | Direct match — asset + price + ts |
| `W` window open | market discovery (PriceEngine) | Need new `market:discovered` EventBus event |
| `C` CLOB snapshot | `price:update` | Has yes/no bid+ask — richer than C event |
| `R` resolution | `market:resolved` | Same — recorder-inferred, still unreliable |
| `K` 1m candle | `crypto:candle` (tf=1m) | Direct match — filter for 1m only |
| `META` session start | *(not in recorder)* | Nuxt plugin startup is equivalent |

**`W` (window open):** Market discovery fetches from Gamma API and populates markets.
We need to emit a `market:discovered` event from `PriceEngine` when new markets are found,
so `SqliteWriter` can INSERT into the `markets` table. Small change to `price-engine.ts`.

---

## Implementation Checklist

### In `market-recorder` (TypeScript)

- [ ] Add `better-sqlite3` dependency: `yarn add better-sqlite3 @types/better-sqlite3`
- [ ] Create `server/recorder/sqlite-writer.ts` with full schema init + prepared statements
- [ ] Add `market:discovered` event to `EventMap` in `types/index.ts`
- [ ] Emit `market:discovered` from `PriceEngine` when discovery returns new markets
- [ ] Register `SqliteWriter` in `server/plugins/recorder-engine.ts`
- [ ] Add `SQLITE_DB_PATH` and `SQLITE_ENABLED` to `.env.example`
- [ ] Test: confirm WAL mode, confirm rows appear on each event
- [ ] Add `/api/sqlite-status` endpoint to dashboard: row counts per table, last write ts

### In `polymarket-data-service` (Laravel)

- [ ] Add `doctrine/dbal` and `php-sqlite3` to PHP deps (for reading SQLite in Laravel)
- [ ] Create `app/Services/Ingest/SqliteSyncService.php`:
  - Open SQLite read-only
  - Read cursors, pull new rows per table
  - Map recorder schema → PostgreSQL schema (field name translation)
  - Batch insert via existing ingest handlers
  - Update cursors on success
- [ ] Create `app/Console/Commands/IngestSyncCommand.php` (`ingest:sync --db=path`)
- [ ] Register `ingest:sync` in the scheduler (every 5 minutes)
- [ ] Handle the recorder → SCHEMA.md field name translation:
  - `markets.condition_id` + `markets.slug` → `windows.id` (slug) + `windows.condition_id`
  - `oracle_ticks.price` → `oracle_ticks.price_usd` + compute `price_bp = price * 100`
  - `clob_snapshots.condition_id` → resolve to `windows.id` via `windows.condition_id` index
- [ ] Add `/health` check: last_synced_at from sync_cursors > 10min → alert

---

## Notes

1. **JSONL files are still written.** Don't remove them. They're the backup, the historical
   archive, and the raw export product. SQLite is additive.

2. **`winner` field from `market:resolved` is still unreliable.** Store it in SQLite as-is,
   but the Laravel ingest pipeline ignores it for `windows.outcome`. Only Gamma API is
   authoritative. The `resolved_ts` is what we want from this event.

3. **`oracle:settle` events** give us the oracle price at the exact moment a 5m or 15m
   window boundary crosses. This is useful for validating break prices but is not the
   same as the market's resolution. Store it in `oracle_settlements`, use it optionally
   as a data quality cross-check.

4. **SOL markets:** The recorder supports SOL but SCHEMA.md only mentions BTC/ETH.
   The `assets` table seeder should include SOL. The SQLite schema uses `TEXT` for asset
   names so SOL data flows through automatically — just add a SOL row to `assets` in Postgres.

5. **Same-machine setup for dev/MVP:** If the recorder and API server are on the same machine,
   skip rsync entirely — point Laravel's `ingest:sync` directly at the recorder's SQLite file.
   This is the simplest possible MVP path.
