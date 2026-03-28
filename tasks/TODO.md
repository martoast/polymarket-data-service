# Polymarket Data Service — Full Build Plan

Stack: Laravel 12 · PostgreSQL + TimescaleDB · Docker + Supervisor · Sanctum API keys · Stripe Cashier

See also: `RECORDER_INTEGRATION.md` for the full recorder → SQLite → PostgreSQL data flow design.

---

## Phase 0 — Recorder Changes (market-recorder, TypeScript)

These changes are made in the `market-recorder` repo, not here. Listed here for sequencing.

- [ ] Add `better-sqlite3` dependency: `yarn add better-sqlite3 @types/better-sqlite3`
- [ ] Add `market:discovered` event to `EventMap` in `types/index.ts`
- [ ] Emit `market:discovered` from `PriceEngine` when Gamma API discovery returns new markets
- [ ] Create `server/recorder/sqlite-writer.ts`:
  - Init schema (markets, oracle_ticks, clob_snapshots, candles_1m, oracle_settlements, sync_cursors)
  - `PRAGMA journal_mode = WAL` and `synchronous = NORMAL`
  - Prepared statements for all inserts
  - Listen to: `market:discovered`, `crypto:trade`, `price:update`, `crypto:candle` (1m only), `oracle:settle`, `market:resolved`
  - Batch clob_snapshots writes (100-row transactions, flush every 1s)
  - All other tables: write immediately per event
- [ ] Register `SqliteWriter` in `server/plugins/recorder-engine.ts`
- [ ] Add `SQLITE_DB_PATH=data/polymarket.db` and `SQLITE_ENABLED=true` to recorder `.env.example`
- [ ] Test: confirm WAL mode, row counts grow on each event type
- [ ] Add sqlite row counts to the `/api/status` health endpoint

---

## Phase 1 — Project Bootstrap

### 1.1 Laravel Project Setup
- [ ] Create new Laravel 12 project (`laravel new polymarket-api`)
- [ ] Remove web routes, views, Vite — API only
- [ ] Add `routes/api.php` as the only route file; confirm in `bootstrap/app.php`
- [ ] Delete `resources/views/` and `resources/js/` and `resources/css/`
- [ ] Update `bootstrap/app.php` to not register `web` middleware group for views
- [ ] Add a root `GET /` endpoint returning API name + version (like mesadirectiva)

### 1.2 Docker & Compose
- [ ] Write `Dockerfile` — Ubuntu 22.04, PHP 8.4 with extensions: `pgsql`, `pdo_pgsql`, `redis`, `pcntl`, `bcmath`, `gd`
- [ ] Write `compose.yaml`:
  - Service `laravel.test` (app)
  - Service `pgsql` — PostgreSQL 16 with persistent volume `sail-pgsql`
  - Service `redis` — Redis 7 (for rate limiting + cache + queue)
  - Bridge network `sail`
  - Ports: `APP_PORT` (default 8001), no Vite
- [ ] Write `supervisord.conf`:
  - Process `php` — `artisan serve --host=0.0.0.0 --port=80`
  - Process `queue-worker-ingest` — `queue:work --queue=ingest,high,default --sleep=3 --tries=3 --max-time=3600`
  - Process `queue-worker-resolution` — `queue:work --queue=resolution,default --sleep=5 --tries=5 --max-time=3600`
  - Process `scheduler` — `while true; do php artisan schedule:run; sleep 60; done`
- [ ] Write `.env.example` with all required vars (see section below)
- [ ] Confirm `docker compose up` starts cleanly with PHP serving on `APP_PORT`

### 1.3 TimescaleDB Setup
- [ ] Add `timescaledb/timescaledb:latest-pg16` as the `pgsql` image in compose (ships with TimescaleDB)
- [ ] Create migration `0001_enable_timescaledb.php` — runs `CREATE EXTENSION IF NOT EXISTS timescaledb;`
- [ ] Confirm extension is active via `SELECT extname FROM pg_extension;`

### 1.4 CORS & Global Middleware
- [ ] Configure `config/cors.php`:
  - `paths` → `['api/*']`
  - `allowed_origins` → `[env('APP_FRONTEND_URL', 'http://localhost:3000')]`
  - `supports_credentials` → `true`
  - `allowed_methods` → `['*']`
  - `allowed_headers` → `['*']`
- [ ] Create `app/Http/Middleware/ForceJsonResponse.php` — sets `Accept: application/json` on all requests so Laravel always returns JSON errors
- [ ] Register `ForceJsonResponse` as a global middleware in `bootstrap/app.php`

### 1.5 .env.example
```
APP_NAME=polymarket-api
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001
APP_PORT=8001
APP_FRONTEND_URL=http://localhost:3000

WWWGROUP=1000
WWWUSER=1000

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=polymarket
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

GAMMA_API_BASE=https://gamma-api.polymarket.com
RESOLUTION_DELAY_MINUTES=5

MAIL_MAILER=log
```

---

## Phase 2 — Database Migrations

Run all migrations in order. Each file should be one table.

### 2.1 Dimension Tables
- [ ] `create_assets_table` — `id SMALLSERIAL PK, symbol TEXT UNIQUE, chain TEXT, oracle_addr TEXT`
- [ ] Seeder: insert BTC and ETH rows immediately after migration
- [ ] `create_api_users_table` — `id, email UNIQUE, password (bcrypt), tier ENUM(free,builder,pro,enterprise) DEFAULT free, is_active BOOL DEFAULT true, stripe_customer_id, stripe_subscription_id, timestamps`

### 2.2 Core Domain Tables
- [ ] `create_windows_table`:
  - `id TEXT PK` (gamma slug: `btc-updown-5m-1773136800`)
  - `asset_id SMALLINT FK assets`
  - `duration_sec SMALLINT` (300 or 900)
  - `break_price_usd NUMERIC(14,2)`
  - `break_price_bp BIGINT`
  - `open_ts BIGINT` (UTC ms)
  - `close_ts BIGINT`
  - `resolved_ts BIGINT NULL`
  - `outcome TEXT NULL CHECK (outcome IN ('YES','NO'))`
  - `condition_id TEXT NULL` (hex conditionId from W event — secondary key)
  - `gamma_slug TEXT NULL`
  - `has_oracle_coverage BOOL DEFAULT false`
  - `has_clob_coverage BOOL DEFAULT false`
  - `recording_gap BOOL DEFAULT false`
  - Indexes: `(asset_id, open_ts DESC)`, `(asset_id, outcome, open_ts DESC)`, `(close_ts)`, `(condition_id)`

- [ ] `create_oracle_ticks_table`:
  - `id BIGSERIAL, asset_id SMALLINT FK, price_usd NUMERIC(14,2), price_bp BIGINT, ts BIGINT NOT NULL`
  - `PRIMARY KEY (id, ts)`
  - After migration: run `SELECT create_hypertable('oracle_ticks', 'ts', chunk_time_interval => 2592000000)` via raw query in migration
  - `CREATE INDEX idx_oracle_asset_ts ON oracle_ticks (asset_id, ts DESC)`
  - Compression policy: `SELECT add_compression_policy('oracle_ticks', BIGINT '604800000')`

- [ ] `create_clob_snapshots_table`:
  - `id BIGSERIAL, window_id TEXT FK windows, asset_id SMALLINT FK assets, yes_ask NUMERIC(6,4), no_ask NUMERIC(6,4), ts BIGINT NOT NULL`
  - `PRIMARY KEY (id, ts)`
  - Hypertable on `ts`; indexes on `(window_id, ts DESC)` and `(asset_id, ts DESC)`
  - Compression policy same as oracle_ticks

- [ ] `create_candles_1m_table`:
  - `id BIGSERIAL, asset_id SMALLINT FK, open_usd NUMERIC(14,2), high_usd NUMERIC(14,2), low_usd NUMERIC(14,2), close_usd NUMERIC(14,2), ts BIGINT NOT NULL`
  - `PRIMARY KEY (id, ts)`
  - Hypertable on `ts`; index on `(asset_id, ts DESC)`

- [ ] `create_oracle_stats_table`:
  - `id BIGSERIAL, asset_id SMALLINT FK, ts BIGINT NOT NULL, bucket_sec SMALLINT` (60/300/600/1800)
  - `high_bp BIGINT, low_bp BIGINT, range_bp INTEGER GENERATED ALWAYS AS (high_bp - low_bp) STORED`
  - `open_bp BIGINT, close_bp BIGINT, trend_bp INTEGER GENERATED ALWAYS AS (close_bp - open_bp) STORED`
  - `tick_count SMALLINT`
  - `PRIMARY KEY (id, ts)`
  - Hypertable on `ts`; index on `(asset_id, bucket_sec, ts DESC)`

- [ ] `create_sessions_audit_table`:
  - `id BIGSERIAL PK`
  - `filename TEXT UNIQUE`
  - `started_at BIGINT, ended_at BIGINT NULL`
  - `event_count INT, oracle_count INT, clob_count INT`
  - `file_size_bytes BIGINT`
  - `ingest_status TEXT DEFAULT 'pending' CHECK ('pending','done','error')`
  - `ingest_error TEXT NULL`
  - `ingested_at BIGINT NULL`

### 2.3 Feature Table
- [ ] `create_window_features_table`:
  - `window_id TEXT PRIMARY KEY REFERENCES windows(id)`
  - `asset TEXT NOT NULL, duration_sec SMALLINT, open_ts BIGINT, close_ts BIGINT`
  - `outcome TEXT NOT NULL`
  - Oracle distance columns at fixed time points: `oracle_dist_bp_at_5m`, `_at_4m`, `_at_3m`, `_at_2m`, `_at_90s`, `_at_1m`, `_at_45s`, `_at_30s`, `_at_15s`, `_final` (all INTEGER NULL)
  - Oracle vol: `oracle_range_5m_bp`, `_10m_bp`, `_15m_bp`, `_5m_at_3m`, `_5m_at_2m` (INTEGER NULL)
  - Oracle trend: `oracle_trend_5m_bp`, `_10m_bp` (INTEGER NULL)
  - Oracle density: `oracle_tick_count INT, oracle_tick_gap_max_ms INT`
  - Crossings: `oracle_crossings_total SMALLINT`, `_last_5m SMALLINT`, `_last_2m SMALLINT`, `oracle_committed_since_ms INT`
  - CLOB: `clob_yes_ask_final NUMERIC(6,4)`, `_min_5m`, `_max_5m`, `_avg_5m`, `clob_spread_final NUMERIC(6,4)`, `clob_snapshot_count INT`, `clob_in_lock_range BOOL`
  - Context: `oracle_range_30m_prior_bp INT`, `oracle_trend_30m_prior_bp INT`, `oracle_crossings_30m_prior SMALLINT`, `hour_utc SMALLINT`, `day_of_week SMALLINT`
  - Quality: `has_full_oracle_coverage BOOL DEFAULT false`, `has_clob_coverage BOOL DEFAULT false`, `recording_gap BOOL DEFAULT false`
  - `computed_at BIGINT NOT NULL`
  - Indexes: `(asset, outcome, open_ts DESC)`, `(open_ts DESC)`, `(hour_utc, asset)`, `(oracle_range_5m_bp, asset)`, `(oracle_committed_since_ms DESC)`

### 2.4 Sanctum Tokens
- [ ] Run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` and migrate `personal_access_tokens`

---

## Phase 3 — Models

- [ ] `Asset` model — `fillable: [symbol, chain, oracle_addr]`, `hasMany` windows, oracle_ticks
- [ ] `Window` model — `fillable: [id, asset_id, ...]`, relationships: `belongsTo Asset`, `hasMany OracleTick (via asset_id + time range)`, `hasMany ClobSnapshot`, `hasOne WindowFeature`
  - Scope `resolved()` — where outcome IS NOT NULL
  - Scope `withCoverage()` — has_oracle_coverage AND has_clob_coverage AND NOT recording_gap
  - Accessor `duration_label` → "5m" or "15m"
- [ ] `OracleTick` model — `fillable: [asset_id, price_usd, price_bp, ts]`
- [ ] `ClobSnapshot` model — `fillable: [window_id, asset_id, yes_ask, no_ask, ts]`
- [ ] `WindowFeature` model — `fillable: [window_id, ...]`, `belongsTo Window`
- [ ] `OracleStat` model — `fillable: [asset_id, ts, bucket_sec, high_bp, low_bp, open_bp, close_bp, tick_count]`
- [ ] `Candle1m` model — `fillable: [asset_id, open_usd, high_usd, low_usd, close_usd, ts]`
- [ ] `SessionAudit` model — `fillable: [filename, started_at, ...]`
- [ ] `ApiUser` model — `HasApiTokens`, `HasFactory`, `Notifiable`; `tier` enum, `is_active`, `stripe_customer_id`; methods `isFreeTier()`, `isBuilderTier()`, `isProTier()`, `historyLimitDays()`, `dailyRateLimit()`

---

## Phase 4 — Auth (Sanctum API Keys)

Pattern: same as mesadirectiva — single token per user, revoke old on login. Token = API key for this service.

- [ ] Install Laravel Sanctum: `composer require laravel/sanctum`
- [ ] Install Laravel Fortify: `composer require laravel/fortify`; publish config; register `FortifyServiceProvider` and `AuthServiceProvider`
- [ ] `AuthController` with:
  - `POST /auth/register` — create ApiUser, send verification email, return token
  - `POST /auth/login` — validate credentials, revoke old tokens, create new token, return `{ token, user }`
  - `POST /auth/logout` — delete current token
  - `GET /auth/me` — return current user + tier
  - `POST /auth/token/regenerate` — revoke old, issue new token (for key rotation)
  - `POST /auth/forgot-password`
  - `POST /auth/reset-password`
- [ ] Fortify actions: `CreateNewUser`, `UpdateUserPassword`, `ResetUserPassword`
- [ ] `EnsureUserIsActive` middleware (same pattern as mesadirectiva) — return 403 + revoke token if `is_active = false`
- [ ] Register middleware aliases in `bootstrap/app.php`:
  - `'active' => EnsureUserIsActive::class`
  - `'tier' => EnforceTierAccess::class`
- [ ] `EnforceTierAccess` middleware — reads `$request->user()->tier` and rejects if tier insufficient for the route; used as `middleware('tier:pro')` on Pro-only endpoints
- [ ] All protected routes under `middleware(['auth:sanctum', 'active'])`

---

## Phase 5 — Rate Limiting

- [ ] Configure rate limiters in `bootstrap/app.php` using `RateLimiter::for()`:
  - `'api-free'` — 100 requests per day per user
  - `'api-builder'` — 10,000 per day per user
  - `'api-pro'` — 100,000 per day per user
  - `'api-enterprise'` — unlimited (return `Response::allow()`)
- [ ] Create `ApplyTierRateLimit` middleware — reads `$user->tier`, applies the correct named limiter; returns `429` with `Retry-After` header on exceeded
- [ ] Register `ApplyTierRateLimit` on all `GET /v1/*` data routes
- [ ] Use Redis as the rate limiter store (`CACHE_STORE=redis` in .env)

---

## Phase 6 — Stripe Billing

Same pattern as mesadirectiva but subscriptions instead of one-time payments.

- [ ] Install Cashier: `composer require laravel/cashier`
- [ ] Run `php artisan cashier:install` and migrate the Cashier tables
- [ ] Add Cashier trait `Billable` to `ApiUser` model
- [ ] Publish Cashier config; set currency, webhook tolerance
- [ ] Create Stripe products + prices manually in dashboard (or via seeder):
  - Builder: `$29/mo` → env `STRIPE_BUILDER_PRICE_ID`
  - Pro: `$99/mo` → env `STRIPE_PRO_PRICE_ID`
- [ ] `BillingController`:
  - `POST /billing/checkout` — creates Stripe Checkout session for subscription; `success_url`, `cancel_url` back to frontend; metadata: `user_id`
  - `GET /billing/portal` — creates Stripe Billing Portal session for plan management/cancellation
  - `GET /billing/subscription` — returns current plan, renewal date, status
- [ ] `WebhookController` at `POST /webhooks/stripe` (public, no auth):
  - Verify signature: `Cashier::constructEvent()`
  - Handle `customer.subscription.created` → set `user.tier = builder|pro` based on price ID
  - Handle `customer.subscription.updated` → same, handles plan changes and pauses
  - Handle `customer.subscription.deleted` → set `user.tier = free`
  - Handle `invoice.payment_failed` → optionally send warning email; keep tier for grace period
- [ ] Register webhook route outside `auth:sanctum` middleware group
- [ ] Add `STRIPE_WEBHOOK_SECRET` to .env.example

---

## Phase 7 — Ingest Pipeline

This is the core backend. Three workers run concurrently: ingest, resolution, feature.

### 7.1 SQLite Sync Worker (primary, real-time)
- [ ] Add `php-sqlite3` extension to Dockerfile
- [ ] Create `app/Services/Ingest/SqliteSyncService.php`:
  - Open recorder's SQLite file read-only
  - Read `sync_cursors` table for last consumed `id` per table
  - Pull new rows: `SELECT * FROM {table} WHERE id > {last_id} ORDER BY id LIMIT 1000`
  - Translate recorder field names → PostgreSQL schema (see RECORDER_INTEGRATION.md mapping table)
  - Batch-insert via existing ingest handlers (reuse same handlers as JSONL pipeline)
  - On success: `UPDATE sync_cursors SET last_id = ?, last_synced_at = ?`
  - Wrap in DB transaction — if Postgres insert fails, don't advance cursor
- [ ] Create `app/Console/Commands/IngestSyncCommand.php`:
  - `php artisan ingest:sync --db=/path/to/polymarket.db`
  - Accepts `--once` flag (single pass) or runs continuously with `--loop` (sleep 30s between passes)
- [ ] Register in scheduler: `$schedule->command('ingest:sync --once')->everyFiveMinutes()`
- [ ] Field translation to handle:
  - `markets.condition_id` + `markets.open_ts/close_ts` → `windows` (compute slug from asset + open_ts + duration)
  - `oracle_ticks.price` → `price_usd`; compute `price_bp = (int)(price * 100)`
  - `clob_snapshots.condition_id` → resolve to `windows.id` via `windows.condition_id` lookup
  - Skip `markets.winner` — do NOT write to `windows.outcome` (unreliable, Gamma API only)

### 7.2 JSONL Ingest Worker (historical backfill)
- [ ] `IngestService` class in `app/Services/Ingest/`:
  - `ingestFile(string $path): void` — reads JSONL line by line, routes to handler by `e` field
  - Handler methods: `handleMeta()`, `handleOracle()`, `handleWindow()`, `handleClob()`, `handleCandle()`, `handleResolution()`
  - Batch collector: accumulate 1000 rows before inserting (for O and C events)
  - Transaction per batch
- [ ] Event handlers:
  - `handleMeta()` — upsert `sessions_audit` row; detect gap from previous session
  - `handleOracle()` — batch-insert into `oracle_ticks`; queue `UpdateOracleStats` job
  - `handleWindow()` — upsert `windows` (slug as PK); compute slug from `ws`, `we`, `a`; queue `ScheduleResolution` job ~5min after `we`
  - `handleClob()` — batch-insert `clob_snapshots`; set `windows.has_clob_coverage = true` if first for this market
  - `handleCandle()` — upsert `candles_1m`
  - `handleResolution()` — update `windows.resolved_ts` ONLY (do NOT set outcome from R event)
- [ ] `IngestFile` job class — dispatched to `ingest` queue; calls `IngestService->ingestFile()`; marks `sessions_audit.ingest_status = done|error`
- [ ] Artisan command `ingest:file {path}` — dispatches `IngestFile` job; useful for manual backfill
- [ ] Artisan command `ingest:directory {path}` — walks directory, dispatches `IngestFile` per `.jsonl` file not yet in `sessions_audit`, ordered by filename
- [ ] Duplicate handling: before dispatching, check `sessions_audit` for filename; skip if `ingest_status = done`

### 7.2 Gap Detection
- [ ] After each `sessions_audit` upsert: query previous session's `ended_at`; if gap > 5 minutes, mark all `windows` overlapping the gap with `recording_gap = TRUE`, `has_full_oracle_coverage = FALSE`
- [ ] A window overlaps the gap if: `open_ts < gap_end AND close_ts > gap_start`

### 7.3 Resolution Worker (Gamma API)
- [ ] `GammaService` class in `app/Services/`:
  - `fetchResolution(string $slug): ?array` — HTTP GET to `{GAMMA_API_BASE}/events?slug={slug}`; parse outcome per SCHEMA.md; return `['outcome' => 'YES'|'NO', 'condition_id' => hex]` or null if not closed
  - Use Laravel's `Http::get()` with user-agent `Mozilla/5.0`
  - Return null if `closed = false` or API error
- [ ] `ResolveWindow` job class — dispatched to `resolution` queue with delay of `RESOLUTION_DELAY_MINUTES` after `close_ts`:
  - Call `GammaService->fetchResolution()`
  - If null (not closed yet): re-dispatch self with 5-minute delay (max 10 retries)
  - If resolved: update `windows.outcome`, `windows.resolved_ts`, `windows.condition_id`; dispatch `ComputeWindowFeatures` job
- [ ] Scheduled command `resolutions:retry` — every 30 minutes; finds windows where `close_ts < now - 10min AND outcome IS NULL`; dispatches `ResolveWindow` for each (catches any dropped jobs)

### 7.4 Feature Worker
- [ ] `WindowFeatureService` class in `app/Services/`:
  - `computeFeatures(string $window_id): void`
  - Load window + its oracle ticks + clob snapshots
  - Compute all columns per SCHEMA.md feature definitions
  - Oracle dist at fixed offsets: find nearest oracle tick to each time point
  - Oracle crossings: count sign changes of `(price_bp - break_price_bp)` across all ticks
  - `oracle_committed_since_ms`: ms since last crossing at close_ts
  - Prior 30min context: query oracle_ticks for `open_ts - 1800000` to `open_ts`
  - CLOB features: aggregate yes_ask over last 5 minutes before close_ts
  - Upsert into `window_features`
- [ ] `ComputeWindowFeatures` job class — dispatched to `default` queue after resolution; calls `WindowFeatureService->computeFeatures()`
- [ ] `UpdateOracleStats` job — after each oracle tick batch, recompute `oracle_stats` for `(asset_id, bucket_sec)` tuples covering the ingested time range

---

## Phase 8 — API Endpoints

All endpoints under `middleware(['auth:sanctum', 'active', 'throttle:api-{tier}'])`.
Use `JsonResource` classes for all responses. Cursor-based pagination throughout.

### 8.1 API Resources (JsonResource)
- [ ] `WindowResource` — id, asset, duration_sec, break_price_usd, open_ts, close_ts, outcome, resolved_ts, has_coverage
- [ ] `WindowFeatureResource` — all feature columns, flattened JSON
- [ ] `OracleTickResource` — asset, price_usd, price_bp, ts
- [ ] `OracleRangeResource` — asset, at, lookback_sec, high_bp, low_bp, range_bp, trend_bp, tick_count
- [ ] `ClobSnapshotResource` — window_id, yes_ask, no_ask, ts
- [ ] `ApiUserResource` — id, email, tier, created_at (never expose password or token)

### 8.2 Windows Endpoint
- [ ] `WindowController@index` → `GET /v1/windows`
  - Params: `asset`, `duration` (5m|15m), `outcome` (YES|NO), `from`, `to`, `has_coverage` (bool), `limit` (max 1000, default 50), `cursor`
  - History limit by tier: free=7 days, builder=90 days, pro=unlimited
  - Response: `{ data: [...], next_cursor, total }`
- [ ] `WindowController@show` → `GET /v1/windows/{id}` — single window by slug

### 8.3 Features Endpoint
- [ ] `WindowFeatureController@index` → `GET /v1/features`
  - Same params as `/v1/windows` plus `columns` (comma-sep list to select), `quality` (strict)
  - `?quality=strict` → auto-adds `has_full_oracle_coverage=true AND recording_gap=false`
  - `?format=csv` → return `Content-Type: text/csv` (Pro tier only, enforced via `tier:pro` middleware)
  - `?format=parquet` → Pro tier only
  - JSON default: paginated `WindowFeatureResource`

### 8.4 Oracle Endpoints
- [ ] `OracleController@ticks` → `GET /v1/oracle/ticks`
  - Params: `asset` (required), `from` (required), `to` (required, max 24h on builder, unlimited on pro), `limit` (max 10000)
- [ ] `OracleController@range` → `GET /v1/oracle/range`
  - Params: `asset` (required), `at` (Unix ms, required), `lookback` (1m|5m|10m|30m)
  - Queries `oracle_stats` for nearest matching row
  - Response: `OracleRangeResource`
- [ ] `OracleController@aligned` → `GET /v1/oracle/aligned`
  - Params: `window_id` (required), `offsets_sec` (comma-sep, e.g. `300,180,120,60,30`)
  - Returns oracle price at each offset before `close_ts`
  - Response: `{ window_id, break_price_bp, points: [{offset_sec, price_bp, dist_bp, ts}] }`

### 8.5 CLOB Endpoint
- [ ] `ClobController@snapshots` → `GET /v1/clob/snapshots`
  - Params: `window_id`, `asset`, `from`, `to`, `limit` (max 10000)
  - At least one of `window_id` or (`asset` + `from` + `to`) must be provided

### 8.6 Markets/Live Endpoint
- [ ] `MarketController@active` → `GET /v1/markets/active`
  - Returns windows where `open_ts <= now AND close_ts >= now AND outcome IS NULL`
  - No pagination, max 20 rows

### 8.8 Backtest Endpoint (Pro tier)
- [ ] `BacktestController@run` → `POST /v1/backtest` — `middleware('tier:pro')`
  - Request body: `asset`, `from`, `to`, `params` object (oracle_dist thresholds, vol range filters, lock range)
  - Sweep variant: `sweep` object with arrays of values + `fixed` object
  - `BacktestService->run()`:
    - Query `window_features` for date range and asset
    - For each param combination, filter windows by oracle/CLOB conditions and simulate outcomes
    - 80/20 train/val split by time
    - Compute: `win_rate`, `sharpe`, `total_trades`, `pnl` per split
    - Sweep: rank combinations by val_sharpe, return top N
  - Response: single result or ranked sweep table

### 8.9 Export Endpoints (Pro tier)
- [ ] `ExportController@sqlite` → `GET /v1/export/sqlite` — `middleware('tier:pro')`
  - Params: `from`, `to` (max 90-day range per request)
  - Streams a portable SQLite file with `window_features` for the date range
  - Use `response()->streamDownload()`
  - Content-Type: `application/x-sqlite3`
- [ ] `ExportController@csv` → `GET /v1/export/csv` — `middleware('tier:pro')`
  - Params: `table` (window_features|windows|oracle_ticks|clob_snapshots), `from`, `to`
  - Stream CSV with headers
- [ ] `ExportController@parquet` → `GET /v1/export/parquet` (optional, requires PHP parquet library or generate via Python subprocess)

### 8.10 Profile / Account Endpoints
- [ ] `ProfileController@show` → `GET /profile` — returns `ApiUserResource`
- [ ] `ProfileController@update` → `PATCH /profile` — update email/password
- [ ] `ProfileController@destroy` → `DELETE /profile` — soft-delete user, revoke tokens

---

## Phase 9 — Request Validation

For each endpoint, create a `FormRequest` class:
- [ ] `WindowIndexRequest` — validate `asset` enum, `duration` enum, `from/to` date parsing (ISO8601 or Unix ms), `limit` max 1000, history limit enforcement by tier
- [ ] `WindowFeatureIndexRequest` — same + `columns` whitelist, `format` enum, tier check for csv/parquet
- [ ] `OracleTicksRequest` — validate `asset`, `from`, `to`, range limit by tier
- [ ] `OracleRangeRequest` — validate `at`, `lookback` enum
- [ ] `OracleAlignedRequest` — validate `window_id` exists, parse `offsets_sec`
- [ ] `ClobSnapshotsRequest` — validate at least one filter provided
- [ ] `BacktestRequest` — validate required fields, `params` keys, sweep array structure
- [ ] `ExportRequest` — validate `from/to`, max range, `table` whitelist

---

## Phase 10 — Error Handling & Response Format

- [ ] Consistent error format for all endpoints:
  ```json
  { "error": "message", "code": "SNAKE_CASE_CODE" }
  ```
- [ ] Register exception handler in `bootstrap/app.php`:
  - `AuthenticationException` → 401 `{ "error": "Unauthenticated", "code": "UNAUTHENTICATED" }`
  - `ValidationException` → 422 with field errors
  - `ThrottleRequestsException` → 429 with `Retry-After` header
  - `ModelNotFoundException` → 404
  - `AuthorizationException` → 403 `{ "error": "Forbidden", "code": "TIER_REQUIRED" }` or `INSUFFICIENT_TIER`
- [ ] All data responses wrapped in `{ "data": [...], "next_cursor": ..., "total": ... }` for lists
- [ ] All single-resource responses: `{ "data": {...} }`

---

## Phase 11 — Health & Monitoring

- [ ] `GET /health` (public) — returns:
  ```json
  { "status": "ok", "db": "ok", "queue": "ok", "last_ingest_at": 1773136800000, "last_oracle_ts": 1773137100000 }
  ```
  - `last_oracle_ts`: max ts from `oracle_ticks` — if > 10min ago, recording may be down
  - `queue`: check failed_jobs count
- [ ] Artisan command `health:check` — called by UptimeRobot or PagerDuty; exits 1 if oracle gap > 5min
- [ ] Scheduled command: `health:check` every 5 minutes; send alert email if recording stalled

---

## Phase 12 — Routes File Summary

Final `routes/api.php` structure:

```php
// Public
Route::get('/', fn() => response()->json(['api' => 'polymarket-data', 'version' => '1.0']));
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/webhooks/stripe', [WebhookController::class, 'handle']);
Route::get('/health', [HealthController::class, 'check']);

// Authenticated
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/token/regenerate', [AuthController::class, 'regenerateToken']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Billing
    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::get('/billing/portal', [BillingController::class, 'portal']);
    Route::get('/billing/subscription', [BillingController::class, 'subscription']);

    // Data — rate limited by tier
    Route::middleware(['throttle:api-tier'])->prefix('v1')->group(function () {
        Route::get('/windows', [WindowController::class, 'index']);
        Route::get('/windows/{id}', [WindowController::class, 'show']);
        Route::get('/features', [WindowFeatureController::class, 'index']);
        Route::get('/oracle/ticks', [OracleController::class, 'ticks']);
        Route::get('/oracle/range', [OracleController::class, 'range']);
        Route::get('/oracle/aligned', [OracleController::class, 'aligned']);
        Route::get('/clob/snapshots', [ClobController::class, 'snapshots']);
        Route::get('/markets/active', [MarketController::class, 'active']);

        // Pro only
        Route::middleware('tier:pro')->group(function () {
            Route::post('/backtest', [BacktestController::class, 'run']);
            Route::get('/export/sqlite', [ExportController::class, 'sqlite']);
            Route::get('/export/csv', [ExportController::class, 'csv']);
            Route::get('/export/parquet', [ExportController::class, 'parquet']);
        });
    });
});
```

---

## Phase 13 — Testing

- [ ] Feature tests for all auth flows (register, login, logout, token regeneration)
- [ ] Feature tests for each data endpoint: returns correct data, respects tier history limits, returns 429 on rate limit exceeded
- [ ] Feature test for tier enforcement: Pro-only endpoints return 403 for Builder/Free users
- [ ] Unit tests for `SqliteSyncService` + `IngestService` — parse each event type correctly, compute derived fields
- [ ] Unit test for `GammaService` — mock HTTP response, assert correct outcome parsing
- [ ] Unit test for `WindowFeatureService` — given known oracle ticks + clob data, assert feature values
- [ ] Unit test for `BacktestService` — given known window_features, assert correct win rate and Sharpe calculation
- [ ] Feature test for Stripe webhook handling: subscription.created sets correct tier, subscription.deleted resets to free
- [ ] Test gap detection: two sessions with >5min gap marks overlapping windows with `recording_gap=TRUE`

---

## Phase 14 — Deployment (Fly.io)

- [ ] Install Fly CLI; `fly launch` from project root
- [ ] `fly.toml`:
  - `[http_service]` on port 80
  - `[build]` using Dockerfile
  - `[env]` for non-secret env vars
- [ ] Provision managed PostgreSQL on Fly with TimescaleDB extension enabled (or use Neon / Supabase for managed Timescale)
- [ ] Provision Upstash Redis (or Fly Redis)
- [ ] Set secrets: `fly secrets set DB_PASSWORD=... STRIPE_SECRET=... STRIPE_WEBHOOK_SECRET=...`
- [ ] Configure Stripe webhook endpoint to `https://api.polymarket-data.com/webhooks/stripe`
- [ ] `fly deploy` — confirm supervisor starts all 4 processes
- [ ] Set up UptimeRobot on `GET /health` — alert if recording stalls

---

## Post-MVP: WebSocket Live Stream

Defer until after paid launch validation.

- [ ] Evaluate: Laravel Reverb (self-hosted) vs Pusher vs Ably
- [ ] `WS /v1/stream?asset=BTC,ETH&events=oracle,clob`
- [ ] Broadcast events from ingest handlers via Laravel Events + `ShouldBroadcast`
- [ ] Auth: validate Bearer token on WS handshake
- [ ] Tier check: only Pro/Enterprise subscribers

---

## Notes & Key Decisions

1. **Why Sanctum not custom API keys**: Sanctum's `personal_access_tokens` table works perfectly for API keys — tokens are hashed, can be revoked, can hold abilities (tier). We alias the token as the "API key" in user-facing docs.

2. **TimescaleDB migrations**: Laravel's `Schema::create()` can't create hypertables — use `DB::statement()` calls after the table exists. Keep these in separate migrations so rollback doesn't fail.

3. **Ingest via SQLite sync**: The recorder writes to SQLite in real-time. The `ingest:sync` command pulls new rows every 5 minutes via cursor. JSONL files are kept as backup and used for historical backfill only. See RECORDER_INTEGRATION.md.

4. **Do NOT use R event outcome**: `w` field in R events is wrong 2.2% of the time. Only use `t` field for `resolved_ts`. All outcome data comes from `ResolveWindow` job → Gamma API.

5. **META oracle params are wrong**: Do not store or use `oracleBase`/`oracleSlope` from META events. Signal guard values come from env vars hardcoded in the strategy.

6. **Cursor pagination**: Use `open_ts` as the cursor key for all time-series endpoints. Encode as base64 JSON: `base64_encode(json_encode(['open_ts' => 1773136800000]))`.

7. **History limits by tier**: Enforce in the FormRequest, not in middleware. The Request class has access to `Auth::user()->tier` to compute the `from` floor.
