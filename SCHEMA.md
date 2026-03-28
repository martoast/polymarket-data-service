# Polymarket Data Service — Schema & Data Architecture

**Goal:** Every query a model builder needs should be fast, pre-joined, and require zero data
cleaning. The raw event stream is our input; clean, feature-rich SQL tables are the product.

---

## Raw Recording Format → Database Mapping

The source of truth is a directory of JSONL session files recorded by BonereaderBot.
Each line is a JSON event. Every event has an `e` field (event type) and a `t` field
(UTC milliseconds timestamp). Here is every event type and exactly how it maps to the DB.

### Event: `META` — Session metadata
Logged once at the start of each session file. Records the strategy config active at that moment.
**Not stored as its own table** — used to annotate session gaps and validate recording continuity.

```json
{
  "e": "META",
  "t": 1774370753942,
  "version": 4,
  "mode": "live",
  "strategy": "bonereader",
  "minLock": 0.84,
  "maxLock": 0.91,
  "oracleBase": 3,
  "oracleSlope": 3
}
```

> **Note:** `oracleBase`/`oracleSlope` in META reflect old shared config values (base=3, slope=3).
> The bot actually uses per-asset env vars (`BONEREADER_BTC_ORACLE_BASE_BP`, etc.) which override
> these. Do NOT use META oracle params for anything — they are wrong. The actual params are
> in CLAUDE.md and applied in strategy.py, not recorded in the file.

**Ingest action:** Parse `t` to detect session start. Log to a `sessions` audit table (see below).

---

### Event: `O` — Oracle tick (Chainlink price update)
Fires every time the Chainlink oracle emits a new price. Approximately 1/second per asset.

```json
{
  "e": "O",
  "t": 1774370754000,
  "a": "ETH",
  "p": 2128.729225
}
```

| JSON field | Type | DB column | Table | Notes |
|-----------|------|-----------|-------|-------|
| `t` | int (UTC ms) | `ts` | `oracle_ticks` | Partition key |
| `a` | string | `asset_id` | `oracle_ticks` | Lookup `assets.id` by symbol |
| `p` | float (USD) | `price_usd` | `oracle_ticks` | Raw USD price |
| `p * 100` | derived | `price_bp` | `oracle_ticks` | Integer basis points (e.g. $87423.50 → 8742350) |

**Ingest action:** Insert one row into `oracle_ticks`. Also trigger an update to the `oracle_stats`
rolling buckets for this asset (1m, 5m, 10m, 30m).

---

### Event: `W` — Window open
Fires when a new binary market window is discovered (either from CLOB activity or on startup).
Includes the break price (the Chainlink oracle price at the time the window opened).

```json
{
  "e": "W",
  "t": 1774370784053,
  "a": "BTC",
  "m": "0xd099...ccb4",
  "q": "Bitcoin Up or Down - March 24, 12:45PM-1:00PM ET",
  "ws": 1774370700000,
  "we": 1774371600000,
  "bp": 69569.4276502289
}
```

| JSON field | Type | DB column | Table | Notes |
|-----------|------|-----------|-------|-------|
| `m` | hex string | `id` (partial) | `windows` | `conditionId` — Polymarket's unique market ID |
| `a` | string | `asset_id` | `windows` | Lookup by symbol |
| `q` | string | *(not stored)* | — | Human-readable title; not useful for queries |
| `ws` | int (UTC ms) | `open_ts` | `windows` | Window start time |
| `we` | int (UTC ms) | `close_ts` | `windows` | Window scheduled end time |
| `bp` | float (USD) | `break_price_usd` | `windows` | The oracle price the market resolves against |
| `bp * 100` | derived | `break_price_bp` | `windows` | Integer basis points |
| `we - ws` | derived | `duration_sec` | `windows` | 300 (5m) or 900 (15m) |

**Gamma slug (the canonical window ID for API queries):**
```python
def build_slug(asset: str, ws_ms: int, duration_ms: int) -> str:
    dur = "5m" if duration_ms <= 305_000 else "15m"
    ws_sec = ws_ms // 1000
    return f"{asset.lower()}-updown-{dur}-{ws_sec}"
# Example: "btc-updown-5m-1774370700"
```
This slug is used as the `id` in the `windows` table and in `GET /v1/windows` queries.
The raw `conditionId` (hex `m` field) is stored as a secondary key for joining back to
CLOB data, which uses it as its market reference.

**Ingest action:** Upsert into `windows`. A window may appear multiple times across sessions
(bot restarts). The first W event with a non-null `bp` wins; subsequent ones for the same `m`
are ignored unless they have a break price and the existing row does not.

---

### Event: `C` — CLOB snapshot
Fires on every price update on the YES or NO order book side for a market.
This is the highest-frequency event — approximately 5+ per second across all active markets.

```json
{
  "e": "C",
  "t": 1774370755043,
  "m": "0xd099...ccb4",
  "yA": 0.87,
  "nA": 0
}
```

| JSON field | Type | DB column | Table | Notes |
|-----------|------|-----------|-------|-------|
| `t` | int (UTC ms) | `ts` | `clob_snapshots` | Partition key |
| `m` | hex string | `window_id` | `clob_snapshots` | FK → `windows.condition_id` |
| `yA` | float 0–1 | `yes_ask` | `clob_snapshots` | Best ask on YES side. 0 = no active ask |
| `nA` | float 0–1 | `no_ask` | `clob_snapshots` | Best ask on NO side |

> **Note:** `yA` and `nA` can both be 0 when no active order exists on that side.
> These rows are still worth storing — the absence of liquidity is signal.
> The bot's entry logic checks `yes_ask` is in [0.84, 0.91] (the lock range).

**What's missing from C events:** There are no bid-side prices (`yB`, `nB`). The bot only
tracks asks. Spread cannot be computed from raw recordings — only ask prices are available.

**Ingest action:** Insert into `clob_snapshots`. Update `windows.has_clob_coverage = TRUE`
if this is the first C event for that window.

---

### Event: `R` — Resolution
Fires when the bot detects a market has resolved (price collapses to 0 or 1 on one side).
This is the bot's **local inference** of the outcome — it may be wrong (~2.2% error rate).

```json
{
  "e": "R",
  "t": 1774370810048,
  "m": "0x4a7e...68f",
  "w": "NO"
}
```

| JSON field | Type | DB column | Table | Notes |
|-----------|------|-----------|-------|-------|
| `t` | int (UTC ms) | `resolved_ts` | `windows` | When the bot detected resolution |
| `m` | hex string | — | — | FK → `windows.condition_id` |
| `w` | "YES"/"NO" | *(not used)* | — | Bot's oracle-inferred outcome — **DO NOT USE** |

> **CRITICAL:** The `w` field from R events is **oracle-inferred and unreliable**. Do NOT
> store it as `outcome` in the `windows` table. The ground-truth outcome comes exclusively
> from the **Gamma API** (see Resolution Pipeline below). The R event is only useful for
> its timestamp (`t`), which tells us when the market resolved.

**Ingest action:** Update `windows.resolved_ts` only. Leave `outcome` NULL until the Gamma
API resolution pipeline runs.

---

### Event: `K` — 1-minute candle
One candle per asset per minute. Used for vol computation in the strategy (backup to raw ticks).

```json
{
  "e": "K",
  "t": 1774370700000,
  "a": "BTC",
  "tf": "1m",
  "o": 69569.43,
  "h": 69569.43,
  "l": 69565.96,
  "c": 69565.96
}
```

| JSON field | Type | DB column | Table | Notes |
|-----------|------|-----------|-------|-------|
| `t` | int (UTC ms) | `ts` | `candles_1m` | Candle open time |
| `a` | string | `asset_id` | `candles_1m` | |
| `tf` | string | *(always "1m")* | — | Only 1m candles are recorded |
| `o` | float | `open_usd` | `candles_1m` | |
| `h` | float | `high_usd` | `candles_1m` | |
| `l` | float | `low_usd` | `candles_1m` | |
| `c` | float | `close_usd` | `candles_1m` | |

**Ingest action:** Upsert into `candles_1m` (same candle may appear across multiple sessions).

---

### Resolution Pipeline (Gamma API)

R events give us a timestamp but not a reliable outcome. The authoritative outcome comes from
polling the Gamma API after market close.

**Slug construction:**
```python
# From W event fields:
ws_sec    = ws_ms // 1000           # e.g. 1774370700
dur       = "5m" if (we - ws) <= 305_000 else "15m"
asset_lc  = asset.lower()           # "btc" or "eth"
slug      = f"{asset_lc}-updown-{dur}-{ws_sec}"
# → "btc-updown-5m-1774370700"
```

**Gamma API call:**
```bash
curl -s -A "Mozilla/5.0" \
  "https://gamma-api.polymarket.com/events?slug=btc-updown-5m-1774370700"
```

**Response parsing (Python):**
```python
event   = response[0]
market  = event["markets"][0]
closed  = market["closed"]              # bool
outcomes     = json.loads(market["outcomes"])       # ["Up", "Down"]
prices_raw   = json.loads(market["outcomePrices"])  # ["1", "0"] or ["0", "1"]
prices       = [float(p) for p in prices_raw]
winner_idx   = prices.index(max(prices))
winner       = outcomes[winner_idx]     # "Up" or "Down"
outcome      = "YES" if winner == "Up" else "NO"
condition_id = market["conditionId"]    # hex — links back to W event's `m` field
```

**Why not use R events?** The bot infers outcome by watching if the oracle price is above or
below `bp` when `we` passes. This is wrong ~2.2% of the time because the oracle can drift
after resolution. Gamma API is authoritative. Always use Gamma.

**Ingest flow:**
1. On each new W event: queue the slug for Gamma polling ~5 min after `we`
2. Gamma returns `closed=true`: update `windows.outcome`, `windows.resolved_ts`,
   then trigger `window_features` computation for that window_id
---

### `sessions` Audit Table
Tracks each session file for gap detection and data quality flags.

```sql
CREATE TABLE sessions (
    id              SERIAL PRIMARY KEY,
    filename        TEXT NOT NULL UNIQUE,
    started_at      BIGINT NOT NULL,       -- META event `t`
    ended_at        BIGINT,                -- last event `t` in file
    event_count     INTEGER,
    oracle_count    INTEGER,
    clob_count      INTEGER,
    signal_count    INTEGER,
    file_size_bytes BIGINT,
    ingest_status   TEXT DEFAULT 'pending' CHECK (ingest_status IN ('pending','done','error')),
    ingest_error    TEXT,
    ingested_at     BIGINT
);
```

A gap between `sessions.ended_at` and the next `sessions.started_at` > 5 minutes means
recording was down. Any windows overlapping that gap get `recording_gap = TRUE` in `windows`
and `has_full_oracle_coverage = FALSE`. This flag propagates to `window_features` so model
builders can exclude noisy data with a single filter.

---

### `candles_1m` Table (full definition)

```sql
CREATE TABLE candles_1m (
    id          BIGSERIAL,
    asset_id    SMALLINT NOT NULL REFERENCES assets(id),
    open_usd    NUMERIC(14, 2) NOT NULL,
    high_usd    NUMERIC(14, 2) NOT NULL,
    low_usd     NUMERIC(14, 2) NOT NULL,
    close_usd   NUMERIC(14, 2) NOT NULL,
    range_usd   NUMERIC(14, 2) GENERATED ALWAYS AS (high_usd - low_usd) STORED,
    ts          BIGINT NOT NULL,    -- candle open time, UTC ms
    PRIMARY KEY (id, ts)
);

SELECT create_hypertable('candles_1m', 'ts', chunk_time_interval => 2592000000);
CREATE INDEX idx_candles_asset_ts ON candles_1m (asset_id, ts DESC);
SELECT add_compression_policy('candles_1m', BIGINT '604800000');
```

---

## Ingest Pipeline Summary

```
JSONL session files
        │
        ▼
[Ingest worker — reads line by line, ordered by `t`]
        │
        ├─ META  → sessions table (audit log + gap detection)
        ├─ O     → oracle_ticks + trigger oracle_stats update
        ├─ W     → windows (upsert) + queue Gamma slug for resolution
        ├─ C     → clob_snapshots + update windows.has_clob_coverage
        ├─ K     → candles_1m (upsert)
        └─ R     → windows.resolved_ts only (DO NOT set outcome from R)
        │
        ▼
[Resolution worker — polls Gamma API ~5min after each window's `we`]
        │
        ├─ windows.outcome = "YES"|"NO"
        └─ trigger window_features computation
        │
        ▼
[Feature worker — computes window_features for newly resolved windows]
        │
        └─ window_features INSERT (one row per resolved window)
```

All three workers can run concurrently. The ingest worker is the bottleneck for historical
backfill; use batch inserts (1000 rows/transaction) for oracle_ticks and clob_snapshots.

---

## Design Principles

1. **Pre-compute everything expensive.** Rolling oracle ranges, CLOB spreads, per-minute oracle
   distances — compute these once at ingest and store them. Never make a subscriber run a
   30-second aggregation to get a feature they'll use in every model.

2. **Partition time-series tables.** Oracle ticks and CLOB snapshots are the highest-volume tables.
   Partition by month so time-range queries skip irrelevant data entirely.

3. **Windows are the unit of analysis.** Everything else (oracle ticks, CLOB snapshots)
   joins back to a window. A window is one binary market instance — 5 or 15 minutes, one asset,
   one break price, one outcome. Model builders think in windows.

4. **Signed oracle distance is always relative to the winning side.** A positive oracle_dist_bp
   means the oracle is on the winning side of the break price. A negative value means it crossed.
   This convention is consistent across all tables.

5. **Integer timestamps everywhere (UTC milliseconds).** No timezone conversions, no DST bugs.
   Every `ts` column is a BIGINT storing `Date.now()` format. API consumers can cast to their
   preferred format; the DB stores raw ms.

6. **Basis points for prices, not floats.** Oracle prices stored as integer basis points
   (1 BP = $0.01 for a $10,000 asset). Avoids float precision issues in range calculations.
   CLOB prices stored as 4-decimal floats (0.0000–1.0000) since they're already cents.

---

## Database: PostgreSQL + TimescaleDB

- **PostgreSQL** for all relational data (windows, features, resolutions)
- **TimescaleDB extension** on `oracle_ticks` and `clob_snapshots` — these are true time-series
  tables. TimescaleDB gives automatic chunk compression, fast range aggregations, and
  `time_bucket()` functions without any extra query complexity.
- **SQLite** for local development and for the raw data export product (subscribers download
  a portable SQLite file they can query locally)

---

## Core Schema

### `assets`
Dimension table. Small, static.

```sql
CREATE TABLE assets (
    id          SMALLSERIAL PRIMARY KEY,
    symbol      TEXT NOT NULL UNIQUE,    -- BTC, ETH
    chain       TEXT NOT NULL,           -- ethereum, polygon
    oracle_addr TEXT NOT NULL            -- Chainlink contract address
);

INSERT INTO assets (symbol, chain, oracle_addr) VALUES
    ('BTC', 'ethereum', '0xF4030086522a5bEEa4988F8cA5B36dbC97BeE88b'),
    ('ETH', 'ethereum', '0x5f4eC3Df9cbd43714FE2740f5E3616155c5b8419');
```

---

### `windows`
One row per binary market instance. Core join target for everything.

```sql
CREATE TABLE windows (
    -- Identity
    id              TEXT PRIMARY KEY,       -- gamma slug: btc-updown-5m-1773136800
    asset_id        SMALLINT NOT NULL REFERENCES assets(id),
    duration_sec    SMALLINT NOT NULL,      -- 300 (5m) or 900 (15m)

    -- Market geometry
    break_price_usd NUMERIC(14, 2) NOT NULL,    -- e.g. 87423.50
    break_price_bp  BIGINT NOT NULL,            -- break_price_usd * 100 as integer

    -- Time bounds
    open_ts         BIGINT NOT NULL,            -- window open, UTC ms
    close_ts        BIGINT NOT NULL,            -- window close (scheduled), UTC ms
    resolved_ts     BIGINT,                     -- actual resolution time, UTC ms

    -- Outcome (NULL until resolved)
    outcome         TEXT CHECK (outcome IN ('YES', 'NO')),

    -- Gamma API ground truth
    gamma_condition_id  TEXT,
    gamma_slug          TEXT,

    -- Data quality flags
    has_oracle_coverage BOOLEAN DEFAULT FALSE,  -- do we have oracle ticks for this window?
    has_clob_coverage   BOOLEAN DEFAULT FALSE,  -- do we have CLOB snapshots?
    recording_gap       BOOLEAN DEFAULT FALSE,  -- was there a recording gap in this window?

    -- Indexes
    CONSTRAINT windows_open_ts_check CHECK (open_ts > 0)
);

CREATE INDEX idx_windows_asset_open ON windows (asset_id, open_ts DESC);
CREATE INDEX idx_windows_outcome ON windows (asset_id, outcome, open_ts DESC);
CREATE INDEX idx_windows_close ON windows (close_ts);
CREATE INDEX idx_windows_open_ts ON windows (open_ts DESC);
```

---

### `oracle_ticks`
Raw Chainlink oracle price history. Highest row count table (~1M+ rows/month).
TimescaleDB hypertable, partitioned by month.

```sql
CREATE TABLE oracle_ticks (
    id          BIGSERIAL,
    asset_id    SMALLINT NOT NULL REFERENCES assets(id),
    price_usd   NUMERIC(14, 2) NOT NULL,
    price_bp    BIGINT NOT NULL,         -- price_usd * 100, integer
    ts          BIGINT NOT NULL,         -- UTC ms

    PRIMARY KEY (id, ts)
);

-- Convert to TimescaleDB hypertable (chunk by month)
SELECT create_hypertable('oracle_ticks', 'ts',
    chunk_time_interval => 2592000000,  -- 30 days in ms
    partitioning_column => 'asset_id',
    number_partitions   => 2
);

-- Critical indexes for range queries
CREATE INDEX idx_oracle_asset_ts ON oracle_ticks (asset_id, ts DESC);

-- Compression policy: compress chunks older than 7 days
SELECT add_compression_policy('oracle_ticks', BIGINT '604800000');
```

**Query pattern this enables:**
```sql
-- Get all BTC oracle ticks in the last 5 minutes of a window
SELECT price_bp, ts
FROM oracle_ticks
WHERE asset_id = 1
  AND ts BETWEEN (close_ts - 300000) AND close_ts
ORDER BY ts;

-- 5-minute oracle range (high-low) at any point in time
SELECT MAX(price_bp) - MIN(price_bp) AS range_bp
FROM oracle_ticks
WHERE asset_id = 1
  AND ts BETWEEN ($t - 300000) AND $t;
```

---

### `clob_snapshots`
Best ask/bid on YES and NO sides. One row per CLOB update event.
TimescaleDB hypertable.

```sql
CREATE TABLE clob_snapshots (
    id          BIGSERIAL,
    window_id   TEXT NOT NULL REFERENCES windows(id),
    asset_id    SMALLINT NOT NULL REFERENCES assets(id),

    -- CLOB prices (0.0000 – 1.0000)
    yes_ask     NUMERIC(6, 4),
    yes_bid     NUMERIC(6, 4),
    no_ask      NUMERIC(6, 4),
    no_bid      NUMERIC(6, 4),

    -- Implied spread
    yes_spread  NUMERIC(6, 4) GENERATED ALWAYS AS (yes_ask - yes_bid) STORED,

    ts          BIGINT NOT NULL,    -- UTC ms

    PRIMARY KEY (id, ts)
);

SELECT create_hypertable('clob_snapshots', 'ts',
    chunk_time_interval => 2592000000
);

CREATE INDEX idx_clob_window_ts ON clob_snapshots (window_id, ts DESC);
CREATE INDEX idx_clob_asset_ts  ON clob_snapshots (asset_id, ts DESC);

SELECT add_compression_policy('clob_snapshots', BIGINT '604800000');
```

---

### `window_features`
**The crown jewel.** Pre-computed feature vector for every resolved window.
This is the table model builders use directly for training — no joins needed.

One row per window. Computed once at resolution time, never updated.

```sql
CREATE TABLE window_features (
    window_id           TEXT PRIMARY KEY REFERENCES windows(id),

    -- Window identity (denormalized for query convenience)
    asset               TEXT NOT NULL,          -- 'BTC' | 'ETH'
    duration_sec        SMALLINT NOT NULL,
    open_ts             BIGINT NOT NULL,
    close_ts            BIGINT NOT NULL,
    outcome             TEXT NOT NULL,          -- ground truth label

    -----------------------------------------------------------------------
    -- ORACLE FEATURES
    -- Signed distance from break price (positive = oracle on YES side)
    -- Computed at fixed time points before close
    -----------------------------------------------------------------------
    oracle_dist_bp_at_5m    INTEGER,    -- 5:00 remaining
    oracle_dist_bp_at_4m    INTEGER,    -- 4:00 remaining
    oracle_dist_bp_at_3m    INTEGER,    -- 3:00 remaining
    oracle_dist_bp_at_2m    INTEGER,    -- 2:00 remaining
    oracle_dist_bp_at_90s   INTEGER,    -- 1:30 remaining
    oracle_dist_bp_at_1m    INTEGER,    -- 1:00 remaining
    oracle_dist_bp_at_45s   INTEGER,    -- 0:45 remaining
    oracle_dist_bp_at_30s   INTEGER,    -- 0:30 remaining
    oracle_dist_bp_at_15s   INTEGER,    -- 0:15 remaining
    oracle_dist_bp_final    INTEGER,    -- last oracle tick before close

    -----------------------------------------------------------------------
    -- ORACLE VOLATILITY (high-low range over lookback window)
    -- Key input for the vol guard
    -----------------------------------------------------------------------
    oracle_range_5m_bp      INTEGER,    -- 5m range at close
    oracle_range_10m_bp     INTEGER,    -- 10m range at close
    oracle_range_15m_bp     INTEGER,    -- 15m range at close
    oracle_range_5m_at_3m   INTEGER,    -- 5m range when 3min remaining (vol guard at signal time)
    oracle_range_5m_at_2m   INTEGER,    -- 5m range when 2min remaining

    -- Oracle trend (final price minus price 5m ago, signed)
    oracle_trend_5m_bp      INTEGER,
    oracle_trend_10m_bp     INTEGER,

    -- Oracle tick count (data density)
    oracle_tick_count       INTEGER,    -- number of oracle ticks in this window
    oracle_tick_gap_max_ms  INTEGER,    -- largest gap between oracle ticks (data quality)

    -----------------------------------------------------------------------
    -- ORACLE CROSSINGS (how many times did oracle cross break price?)
    -- High crossings = choppy regime = bad for the strategy
    -----------------------------------------------------------------------
    oracle_crossings_total      SMALLINT,   -- total break price crossings in window
    oracle_crossings_last_5m    SMALLINT,   -- crossings in final 5 minutes
    oracle_crossings_last_2m    SMALLINT,   -- crossings in final 2 minutes
    oracle_committed_since_ms   INTEGER,    -- ms since oracle last crossed break (at close)
                                            -- high value = oracle has been committed for a while

    -----------------------------------------------------------------------
    -- CLOB FEATURES
    -- CLOB price and spread over the last 5 minutes of the window
    -----------------------------------------------------------------------
    clob_yes_ask_final          NUMERIC(6, 4),  -- yes_ask at last snapshot before close
    clob_yes_ask_min_5m         NUMERIC(6, 4),
    clob_yes_ask_max_5m         NUMERIC(6, 4),
    clob_yes_ask_avg_5m         NUMERIC(6, 4),
    clob_spread_final           NUMERIC(6, 4),  -- yes_ask - yes_bid at last snapshot
    clob_snapshot_count         INTEGER,

    -- Lock range hit (was the yes_ask ever in [0.84, 0.91] in last 5m?)
    clob_in_lock_range          BOOLEAN,

    -----------------------------------------------------------------------
    -- CONTEXT FEATURES
    -- Broader market regime context
    -----------------------------------------------------------------------
    -- What was the oracle doing in the 30 minutes BEFORE this window?
    oracle_range_30m_prior_bp   INTEGER,    -- volatility in prior 30min
    oracle_trend_30m_prior_bp   INTEGER,    -- trend in prior 30min
    oracle_crossings_30m_prior  SMALLINT,   -- crossings in prior 30min (regime indicator)

    -- Hour of day (UTC) — time-of-day regime
    hour_utc                    SMALLINT,
    day_of_week                 SMALLINT,   -- 0=Monday, 6=Sunday

    -----------------------------------------------------------------------
    -- DATA QUALITY FLAGS
    -----------------------------------------------------------------------
    has_full_oracle_coverage    BOOLEAN DEFAULT FALSE,  -- oracle ticks throughout window
    has_clob_coverage           BOOLEAN DEFAULT FALSE,
    recording_gap               BOOLEAN DEFAULT FALSE,  -- gap in raw data for this window

    computed_at                 BIGINT NOT NULL         -- UTC ms when features were computed
);

-- Indexes for model training queries
CREATE INDEX idx_wf_asset_outcome   ON window_features (asset, outcome, open_ts DESC);
CREATE INDEX idx_wf_open_ts         ON window_features (open_ts DESC);
CREATE INDEX idx_wf_signal_fired    ON window_features (asset, signal_fired, outcome);
CREATE INDEX idx_wf_hour            ON window_features (hour_utc, asset);
CREATE INDEX idx_wf_oracle_range    ON window_features (oracle_range_5m_bp, asset);
CREATE INDEX idx_wf_committed       ON window_features (oracle_committed_since_ms DESC);
```

**What a model builder does with this:**
```sql
-- Ready-to-use classification dataset for BTC, no joins
SELECT
    oracle_dist_bp_at_2m,
    oracle_dist_bp_at_1m,
    oracle_range_5m_bp,
    oracle_crossings_last_5m,
    oracle_committed_since_ms,
    clob_yes_ask_final,
    signal_oracle_dist_bp,
    hour_utc,
    outcome     -- label: YES or NO
FROM window_features
WHERE asset = 'BTC'
  AND has_full_oracle_coverage = TRUE
  AND recording_gap = FALSE
  AND outcome IS NOT NULL
ORDER BY open_ts;
```

Zero joins. Zero aggregation. One query = training dataset.

---

### `oracle_stats`
Pre-computed rolling statistics at regular intervals. Enables ultra-fast oracle range
lookups without scanning raw ticks.

```sql
CREATE TABLE oracle_stats (
    id              BIGSERIAL,
    asset_id        SMALLINT NOT NULL REFERENCES assets(id),
    ts              BIGINT NOT NULL,            -- timestamp of this stats snapshot (UTC ms)
    bucket_sec      SMALLINT NOT NULL,          -- stats window: 60, 300, 600, 1800

    high_bp         BIGINT NOT NULL,
    low_bp          BIGINT NOT NULL,
    range_bp        INTEGER NOT NULL GENERATED ALWAYS AS (high_bp - low_bp) STORED,
    open_bp         BIGINT NOT NULL,
    close_bp        BIGINT NOT NULL,
    tick_count      SMALLINT NOT NULL,
    trend_bp        INTEGER NOT NULL GENERATED ALWAYS AS (close_bp - open_bp) STORED,

    PRIMARY KEY (id, ts)
);

SELECT create_hypertable('oracle_stats', 'ts',
    chunk_time_interval => 2592000000
);

CREATE INDEX idx_ostats_asset_bucket_ts ON oracle_stats (asset_id, bucket_sec, ts DESC);
```

Computed by a background job every minute. Makes queries like "what was the 5m oracle range
at 14:37:22?" instant — just look up the nearest row instead of scanning thousands of ticks.

---

## Derived / Materialized Views

## API Endpoints — Full Design

### Authentication & Rate Limiting

```
Authorization: Bearer <api_key>
X-Tier: free | builder | pro | enterprise
```

Rate limits enforced per key at the API gateway. Exceeded requests return `429` with
`Retry-After` header.

---

### Windows API

```
GET /v1/windows
```
Query all resolved windows.

**Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `asset` | `BTC,ETH` | Filter by asset (comma-separated) |
| `duration` | `5m,15m` | Window duration |
| `outcome` | `YES,NO` | Filter by resolution |
| `from` | ISO8601 or Unix ms | Open timestamp ≥ |
| `to` | ISO8601 or Unix ms | Open timestamp ≤ |
| `has_coverage` | bool | Only windows with full oracle + CLOB coverage |
| `limit` | int (max 1000) | Page size |
| `cursor` | string | Pagination cursor (opaque, from prev response) |

**Response:**
```json
{
  "data": [
    {
      "id": "btc-updown-5m-1773136800",
      "asset": "BTC",
      "duration_sec": 300,
      "break_price_usd": 87423.50,
      "open_ts": 1773136800000,
      "close_ts": 1773137100000,
      "outcome": "YES",
      "resolved_ts": 1773137108230,
      "has_coverage": true
    }
  ],
  "next_cursor": "eyJvcGVuX3RzIjoxNzczMTM2ODAwMDAwfQ==",
  "total": 4722
}
```

---

### Features API

```
GET /v1/features
```
Pre-computed feature vectors for model training. **The primary endpoint for model builders.**

**Parameters:** Same as `/v1/windows`, plus:

| Param | Type | Description |
|-------|------|-------------|
| `columns` | string | Comma-separated list of feature columns to return. Default: all. |
| `format` | `json,csv,parquet` | Response format (csv/parquet for bulk, Pro tier) |
| `quality` | `strict` | If set, filters to `has_full_oracle_coverage=TRUE AND recording_gap=FALSE` |

**Response (JSON):**
```json
{
  "data": [
    {
      "window_id": "btc-updown-5m-1773136800",
      "asset": "BTC",
      "open_ts": 1773136800000,
      "outcome": "YES",
      "oracle_dist_bp_at_2m": 34,
      "oracle_dist_bp_at_1m": 31,
      "oracle_range_5m_bp": 18,
      "oracle_crossings_last_5m": 0,
      "oracle_committed_since_ms": 240000,
      "clob_yes_ask_final": 0.8850,
      "hour_utc": 14
    }
  ],
  "columns": ["window_id", "asset", "open_ts", "outcome", "..."],
  "next_cursor": "...",
  "total": 4722
}
```

**Response (CSV, Pro tier):**
Returns `Content-Type: text/csv` with all columns. Suitable for direct `pd.read_csv()`.

---

### Oracle API

```
GET /v1/oracle/ticks
```
Raw oracle tick stream.

| Param | Type | Description |
|-------|------|-------------|
| `asset` | `BTC,ETH` | Required |
| `from` | Unix ms | Required |
| `to` | Unix ms | Required (max 24h range on Builder, unlimited on Pro) |
| `limit` | int (max 10000) | |

```
GET /v1/oracle/range
```
Pre-computed range stats. No raw tick scanning needed.

| Param | Type | Description |
|-------|------|-------------|
| `asset` | `BTC,ETH` | Required |
| `at` | Unix ms | Timestamp to compute range at |
| `lookback` | `1m,5m,10m,30m` | Lookback window |

**Response:**
```json
{
  "asset": "BTC",
  "at": 1773136800000,
  "lookback_sec": 300,
  "high_bp": 8754200,
  "low_bp": 8752800,
  "range_bp": 1400,
  "trend_bp": -300,
  "tick_count": 47
}
```

```
GET /v1/oracle/aligned
```
Oracle ticks aligned to window time points — returns oracle price at fixed offsets before
a window's close. Useful for building aligned time-series features.

| Param | Type | Description |
|-------|------|-------------|
| `window_id` | string | Required |
| `offsets_sec` | `300,180,120,60,30` | Comma-separated seconds before close |

**Response:**
```json
{
  "window_id": "btc-updown-5m-1773136800",
  "break_price_bp": 8742350,
  "points": [
    { "offset_sec": 300, "price_bp": 8756200, "dist_bp": 13850, "ts": 1773136800000 },
    { "offset_sec": 180, "price_bp": 8755400, "dist_bp": 13050, "ts": 1773136920000 },
    { "offset_sec": 120, "price_bp": 8754900, "dist_bp": 12550, "ts": 1773136980000 },
    { "offset_sec": 60,  "price_bp": 8754100, "dist_bp": 11750, "ts": 1773137040000 },
    { "offset_sec": 30,  "price_bp": 8753800, "dist_bp": 11450, "ts": 1773137070000 }
  ]
}
```

---

### CLOB API

```
GET /v1/clob/snapshots
```
Raw CLOB tick stream.

| Param | Type | Description |
|-------|------|-------------|
| `window_id` | string | Filter to a specific window |
| `asset` | `BTC,ETH` | Filter by asset |
| `from` | Unix ms | |
| `to` | Unix ms | |

---

### Backtest API (Pro tier)

```
POST /v1/backtest
```
Server-side parameter sweep. Send a strategy config, get backtest results back.
Eliminates the need to download data and run sweeps locally.

**Request body:**
```json
{
  "asset": "BTC",
  "from": "2026-01-01",
  "to": "2026-03-01",
  "params": {
    "min_lock": 0.84,
    "max_lock": 0.91,
    "oracle_base_bp": 10,
    "oracle_slope_bp": 6,
    "max_oracle_range_bp": 40,
    "pause_after_loss_min": 20,
    "clip_pct": 0.10,
    "starting_bankroll": 100
  }
}
```

**Response:**
```json
{
  "train_sharpe": 0.521,
  "val_sharpe": 0.812,
  "train_trades": 312,
  "val_trades": 73,
  "train_wr": 0.981,
  "val_wr": 0.986,
  "train_pnl": 284.50,
  "val_pnl": 75.48,
  "train_val_gap": -0.291,
  "split_ts": 1774000000000,
  "bankroll_curve": [ ... ]    -- daily bankroll values, optional
}
```

**Sweep variant — test multiple param combinations:**
```json
{
  "asset": "BTC",
  "from": "2026-01-01",
  "to": "2026-03-01",
  "sweep": {
    "oracle_base_bp": [8, 10, 12],
    "oracle_slope_bp": [4, 6, 8],
    "max_oracle_range_bp": [35, 40, 50, null]
  },
  "fixed": {
    "min_lock": 0.84,
    "max_lock": 0.91,
    "clip_pct": 0.10
  }
}
```

Returns a ranked table of all combinations. This is the product that eliminates the need for
the customer to write and run sweep scripts themselves.

---

### Export API (Pro tier)

```
GET /v1/export/sqlite
```
Download a portable SQLite file containing the full `window_features` table for a date range.
Customers can do local queries, train models offline, use with pandas/DuckDB etc.

```
GET /v1/export/csv?table=window_features&from=2026-01-01&to=2026-03-01
GET /v1/export/parquet?table=window_features&from=2026-01-01&to=2026-03-01
```

---

### Live Stream (Pro tier)

```
WS /v1/stream
```

```json
{ "subscribe": ["oracle", "clob", "signal"], "assets": ["BTC"] }
```

Events arrive as they're recorded:
```json
{ "type": "oracle", "asset": "BTC", "price_bp": 8754100, "ts": 1773137040000 }
{ "type": "signal", "window_id": "btc-updown-5m-...", "entry_price": 0.887, "oracle_dist_bp": 31, "ts": 1773137040000 }
```

---

## Data Freshness & SLAs

| Tier | Historical delay | Live stream delay |
|------|-----------------|-------------------|
| Free | 1 hour | Not available |
| Builder | 5 minutes | Not available |
| Pro | Real-time | ~500ms |
| Enterprise | Real-time | ~200ms |

`window_features` rows are computed within 2 minutes of resolution.
`oracle_stats` (rolling stats table) updated every 60 seconds.

---

## Partitioning Summary

| Table | Partitioned? | Partition key | Chunk interval |
|-------|-------------|---------------|----------------|
| `oracle_ticks` | Yes (TimescaleDB) | ts (+ asset_id) | 30 days |
| `clob_snapshots` | Yes (TimescaleDB) | ts | 30 days |
| `oracle_stats` | Yes (TimescaleDB) | ts | 30 days |
| `windows` | No | — | (small, ~50k rows/year) |
| `window_features` | No | — | (one row per window) |

---

## Storage Estimates

Based on observed recording rate (~1 oracle tick/second, ~5 CLOB updates/second):

| Table | Rows/month | Size/month (compressed) |
|-------|-----------|------------------------|
| `oracle_ticks` | ~5M | ~80 MB |
| `clob_snapshots` | ~13M | ~200 MB |
| `oracle_stats` | ~90k | ~5 MB |
| `windows` | ~4,000 | <1 MB |
| `window_features` | ~4,000 | <1 MB |

**Total: ~290 MB/month raw, ~80 MB/month compressed.**
Full year of history: ~1 GB compressed. Extremely manageable.
