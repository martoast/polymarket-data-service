# Polymarket Data Service — Vision & Product Plan

**One-liner:** A pay-as-you-go API that records Polymarket binary market data 24/7 — CLOB snapshots,
Chainlink oracle ticks, resolutions — so bot builders don't have to run their own recorder.

---

## The Problem

Building a profitable Polymarket bot requires months of high-quality recorded data before you can
run meaningful backtests. Right now, everyone who wants to do this has to:

1. Run their own 24/7 WebSocket recorder (server costs, uptime management, reconnect logic)
2. Wait weeks or months before they have enough data to backtest
3. Deal with gaps, crashes, and bad session files constantly
4. Write their own pipeline to fetch Gamma API resolutions and join everything together

This is a solved problem that every serious builder duplicates from scratch. It's also a massive
barrier — most people give up before they have enough data to even know if their strategy works.

**If this API existed when I started, I would have paid for it immediately.**

---

## The Solution

A hosted service that:
- Records Polymarket BTC/ETH Up-Down binary markets 24/7 with no gaps
- Normalizes all data into a clean SQLite/Postgres schema
- Exposes it via a REST API (and optionally WebSocket live stream)
- Auto-fetches Gamma API resolutions so every window has a ground-truth outcome label
- Lets subscribers query historical windows, oracle ticks, CLOB snapshots, and signals

---

## Who Pays For This

**Primary:** Algo traders and bot builders working on Polymarket binary markets
- They have the intent to trade real money
- They understand the value of high-quality labeled data
- They're already paying for servers, APIs, and data feeds elsewhere
- A working strategy earns back the subscription in days

**Secondary:** Researchers and quants studying prediction market microstructure
- CLOB + oracle data at the tick level is academically interesting
- No public dataset exists for this

**Why they pay and don't DIY:**
- Bootstrapping months of historical data from scratch takes months
- Historical data is available immediately on signup (retroactive from service launch)
- Uptime is someone else's problem
- Clean, labeled data (with resolution ground truth) is genuinely hard to maintain

---

## Core Data Types

Everything maps to the existing recording format from BonereaderBot:

| Type | Description | Key Fields |
|------|-------------|------------|
| **Window** | A single 5m or 15m binary market | asset, duration, break_price, open_ts, close_ts, outcome |
| **Oracle tick** | Chainlink price at a moment in time | asset, price_usd, ts |
| **CLOB snapshot** | Best ask on yes/no side | market_id, yes_ask, no_ask, ts |
| **Resolution** | Ground-truth outcome (from Gamma API) | market_id, outcome, resolved_ts |
| **Signal** | A trade signal that passed all guards | market_id, entry_price, oracle_dist_bp, minutes_remaining, ts |

This is exactly what the BonereaderBot records. The work is: store it in SQL instead of JSONL,
add a resolution join pipeline, and wrap it in an API.

---

## API Design (MVP)

### Authentication
- API key in `Authorization: Bearer <key>` header
- Keys provisioned on signup, scoped to a tier

### Endpoints

```
GET  /v1/windows
     ?asset=BTC&duration=5m&from=2026-01-01&to=2026-03-01&outcome=YES
     Returns: list of resolved windows with all metadata

GET  /v1/oracle
     ?asset=BTC&from=2026-03-01T00:00Z&to=2026-03-01T01:00Z
     Returns: oracle tick history (price, timestamp)

GET  /v1/clob
     ?market_id=btc-updown-5m-1773136800&from=...&to=...
     Returns: CLOB snapshots (yes_ask, no_ask, timestamp)

GET  /v1/signals
     ?asset=BTC&from=...&to=...
     Returns: signals that fired, with oracle distance and entry price

GET  /v1/markets/active
     Returns: currently open windows (live)

WS   /v1/stream
     ?asset=BTC,ETH&events=oracle,clob,signal
     Live stream of events as they happen
```

### Response Format
Standard JSON, paginated with cursor-based pagination for large queries.

---

## SQLite Schema (MVP)

```sql
CREATE TABLE windows (
    id          TEXT PRIMARY KEY,   -- e.g. btc-updown-5m-1773136800
    asset       TEXT NOT NULL,      -- BTC | ETH
    duration    TEXT NOT NULL,      -- 5m | 15m
    break_price REAL NOT NULL,
    open_ts     INTEGER NOT NULL,
    close_ts    INTEGER NOT NULL,
    outcome     TEXT,               -- YES | NO | NULL (unresolved)
    resolved_ts INTEGER
);

CREATE TABLE oracle_ticks (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    asset   TEXT NOT NULL,
    price   REAL NOT NULL,
    ts      INTEGER NOT NULL
);

CREATE TABLE clob_snapshots (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    market_id   TEXT NOT NULL,
    yes_ask     REAL,
    no_ask      REAL,
    ts          INTEGER NOT NULL
);

CREATE TABLE signals (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    market_id           TEXT NOT NULL,
    asset               TEXT NOT NULL,
    entry_price         REAL NOT NULL,
    oracle_dist_bp      REAL NOT NULL,
    minutes_remaining   REAL NOT NULL,
    oracle_range_bp     REAL,         -- vol guard value at signal time
    ts                  INTEGER NOT NULL
);
```

---

## Pricing Model

### Tiers

| Tier | Price | History | API Rate | Raw Export | Live Stream |
|------|-------|---------|----------|------------|-------------|
| **Free** | $0/mo | 7 days | 100 req/day | No | No |
| **Builder** | $29/mo | 90 days | 10k req/day | No | No |
| **Pro** | $99/mo | Full history | 100k req/day | Yes (CSV/SQLite) | Yes |
| **Enterprise** | Custom | Full history | Unlimited | Yes | Yes + dedicated support |

### Why These Numbers
- $29/mo is below the cost of running your own VPS + recording overhead
- $99/mo is easily justified if you're trading with a $1k+ bankroll (2 good days pays it back)
- Full history raw export (Pro) is the high-value unlock — this is the product for serious builders
- Free tier drives signups and lets people evaluate data quality

---

## Go-To-Market

**Phase 1 — Build and dogfood (now → 4 weeks)**
- Convert BonereaderBot to write to SQLite in addition to JSONL
- Build the REST API layer (FastAPI)
- Import all existing session data retroactively
- Run it privately for your own use — validate data completeness

**Phase 2 — Closed beta (4-8 weeks)**
- Post in Polymarket Discord and relevant crypto quant communities
- Offer 3 months free to first 10 users in exchange for feedback
- Key questions: which endpoints are actually used? what's missing?

**Phase 3 — Paid launch (8-12 weeks)**
- Add Stripe billing, API key management
- Write landing page copy focused on the time-to-backtest problem
- Launch on Product Hunt, Hacker News Show HN, crypto Twitter

**Distribution channels:**
- Polymarket community Discord (most direct)
- Crypto quant/algo trading subreddits
- Twitter/X — show actual backtest results using the data
- Developer-focused crypto newsletters

---

## Competitive Moat

1. **Historical depth** — every day running adds to a dataset competitors can't replicate
2. **Data quality** — resolution joins via Gamma API, not oracle inference (2.2% error rate issue eliminated)
3. **Market-specific** — purpose-built for Polymarket binary markets, not generic price feeds
4. **Signal layer** — not just raw data, but strategy-ready derived signals (oracle distance, vol guard values)
5. **Trust** — you have real trading results proving the data quality is good enough to trade with

---

## Technical Architecture

```
[BonereaderBot recorder]
        ↓ writes events
[SQLite DB on recording server]
        ↓ synced hourly
[Primary DB (Fly.io Postgres)]
        ↓ serves
[FastAPI REST + WebSocket server]
        ↓
[API consumers / subscribers]
```

### Stack
- **Recorder:** existing BonereaderBot (Node.js), modified to write to SQLite
- **API server:** FastAPI (Python) — matches existing strategy codebase language
- **Database:** SQLite for dev/MVP, PostgreSQL via Fly.io for prod
- **Auth:** Simple API key table in DB, bcrypt-hashed
- **Billing:** Stripe subscriptions + webhooks to manage key tier
- **Hosting:** Fly.io ($3-10/mo for MVP scale)
- **Resolution pipeline:** Existing `fetch_resolutions.py` logic, run as a cron job

### Recording reliability
- Reconnect on disconnect (already implemented in BonereaderBot)
- Health check endpoint + PagerDuty/UptimeRobot alert if recording stalls > 5 minutes
- Sessions write to SQLite with a transaction per event batch — no partial writes

---

## MVP Scope (What to Build First)

Minimum to validate: can someone pay, get an API key, and query windows + oracle data?

1. SQLite schema + BonereaderBot writer changes (2-3 days)
2. Retroactive import of existing sessions (1 day)
3. FastAPI server with `/v1/windows` and `/v1/oracle` endpoints (2-3 days)
4. API key auth middleware (1 day)
5. Simple landing page with pricing (1-2 days)
6. Stripe integration + key provisioning (2-3 days)

**Total MVP: ~2 weeks of focused work**

Everything else (live streaming, CSV exports, CLOB endpoint) is post-validation.

---

## Key Risks

| Risk | Mitigation |
|------|------------|
| Polymarket changes market structure or API | Architecture is event-driven; adapt recorder |
| Recording downtime creates gaps | Health monitoring + alert; document gap windows |
| Not enough demand at $29 | Validate with free beta users first |
| Data privacy / ToS concerns | Review Polymarket ToS; this is public market data |
| Scaling costs exceed revenue | Start SQLite, move to Postgres only when needed |

---

## Success Metrics

- Phase 2: 10 closed beta users actively using the API
- Phase 3 launch: 50 signups in first month, 20% conversion to paid
- 6 months: 100 paid subscribers = $2,900-$9,900/mo ARR
- 12 months: 500 paid subscribers = sustainable solo business

---

## What Makes This Worth Building

You already have the hardest parts:
1. A working recorder with months of clean data
2. Proof that the data is good enough to trade with (real money, 98% win rate)
3. Deep domain knowledge of what bot builders actually need
4. The ground-truth pipeline (Gamma API resolution joins) that nobody else has

The engineering is straightforward. The moat is the data and the credibility.
