# Recorder Migration: Nuxt3 → Laravel

Move the entire market-recorder (Nuxt3 app, port 3007) into this Laravel app.
The recorder runs as a long-lived `artisan recorder:start` command inside the
existing supervisor, writing directly to PostgreSQL — no more SQLite bridge,
no more separate app, no more cross-process compatibility problems.

## Why

- Two apps (Nuxt3 + Laravel) sharing a SQLite file is fragile (WAL version mismatch)
- No real reason to have `recorder.fullstacklabs.org` as a public subdomain
- Supervisor is already here, PHP + ReactPHP handles async WS natively
- Admin can watch recorder health inside the existing web app at `/admin/recorder`

## Architecture (target state)

```
ReactPHP Event Loop (inside artisan recorder:start)
├── RTDS WebSocket  → Chainlink oracle prices (BTC/ETH/SOL)
│     └─ on tick  → insert oracle_ticks + CandleService::tick()
│                    └─ on 1m candle close → insert candles_1m
├── CLOB WebSocket  → Polymarket bid/ask updates
│     └─ on update → buffer ClobRow[] → batch-insert clob_snapshots every 1s
│     └─ on resolved → update windows.outcome
└── Gamma Poller    → periodic timer every 20s
      └─ discover slug-based markets → upsert windows → subscribe CLOB to new tokens
```

Status (row counts, last-tick timestamps, WS state) is stored in Redis and
exposed via `/admin/recorder/status` (JSON) — the admin Blade page polls it.

---

## Checklist

### Phase 0 — Cleanup old recorder infrastructure
- [ ] Remove `ingest-sync` program from `docker/supervisord.conf`
- [ ] Remove `recorder_data` volume reference from `compose.yaml` (already done — now a bind mount to the old recorder data dir; remove entirely)
- [ ] Remove `RECORDER_SQLITE_FILE` from `.env` and `.env.example`
- [ ] Remove `SQLITE_DB_PATH` env reference from `config/services.php` recorder section
- [ ] Delete `app/Services/Ingest/SqliteSyncService.php`
- [ ] Delete `app/Console/Commands/IngestSyncCommand.php`
- [ ] Remove `ingest:sync` schedule entry from `routes/console.php` (if present)
- [ ] Remove `recorder.sqlite_file` from `config/services.php` (keep `gamma_base` + `resolution_delay_minutes`)
- [ ] Run `docker compose up -d` to apply supervisor change (no rebuild needed)

### Phase 1 — Install PHP async dependencies

```bash
composer require ratchet/pawl react/event-loop react/http
```

- [ ] Add `ratchet/pawl ^0.4` (ReactPHP async WS client — handles PING/PONG, reconnect)
- [ ] Add `react/event-loop ^1.5` (async event loop — comes with pawl, pin explicitly)
- [ ] Add `react/http ^1.9` (async HTTP — for non-blocking Gamma API calls)
- [ ] Rebuild container: `docker compose up -d --build`

### Phase 2 — Asset & market config

- [ ] Create `app/Recorder/AssetConfig.php` — static map of asset → slug prefix + chainlink symbol + question keyword, same as `ASSET_CONFIG` in the old recorder:
  ```php
  const ASSETS = [
      'BTC' => ['slug_prefix' => 'btc', 'chainlink_symbol' => 'btc/usd', 'question' => 'Bitcoin'],
      'ETH' => ['slug_prefix' => 'eth', 'chainlink_symbol' => 'eth/usd', 'question' => 'Ethereum'],
      'SOL' => ['slug_prefix' => 'sol', 'chainlink_symbol' => 'sol/usd', 'question' => 'Solana'],
  ];
  ```
- [ ] Add `RECORDED_ASSETS=BTC,ETH,SOL` to `.env` + `.env.example`
- [ ] Add `RTDS_WS_URL` (optional override) to `.env.example`
- [ ] Add `CLOB_WS_URL` (optional override) to `.env.example`

### Phase 3 — Recorder services

All files go in `app/Recorder/`.

#### 3a. CandleService
- [ ] Create `app/Recorder/CandleService.php`
  - Holds in-memory candle state per asset: `[asset => ['open','high','low','close','volume','ts']]`
  - `tick(string $asset, float $price, int $timestampMs): ?array`
    - Returns completed candle array when the 1-minute bucket rolls over, null otherwise
  - Logic: bucket = `floor($ts / 60000) * 60000`; if bucket changes → close old candle + open new one

#### 3b. MarketDiscoveryService
- [ ] Create `app/Recorder/MarketDiscoveryService.php`
  - Extends / wraps existing `GammaService` for slug-based discovery (not just resolution)
  - `discoverWindows(array $assets): array` — for each asset + duration (5m, 15m), generate
    slug candidates for previous / current / next windows:
    ```
    {asset_prefix}-updown-{duration}-{unix_ts_sec}
    ```
    where the unix timestamp is the **open** time (floor to window boundary)
  - Calls `GET {GAMMA_API_BASE}/events?slug={slug}` for each candidate
  - Returns array of discovered market data: `[condition_id, slug, yes_token_id, no_token_id, open_ts, close_ts, break_price]`
  - Upserts into `windows` table (INSERT OR IGNORE equivalent: `DB::table('windows')->insertOrIgnore(...)`)
  - Returns token IDs that are newly discovered (for CLOB subscription)

#### 3c. ClobFeedService
- [ ] Create `app/Recorder/ClobFeedService.php`
  - Manages the Polymarket CLOB WebSocket connection via `ratchet/pawl`
  - `connect(LoopInterface $loop, callable $onMessage, callable $onClose): void`
  - Sends initial subscription: `{"type":"market","assets_ids":[...],"custom_feature_enabled":true}`
  - Mid-connection subscribe: `{"operation":"subscribe","assets_ids":[...]}`
  - PING/PONG keepalive: send text `"PING"` every 10s, reconnect on pong timeout (30s stale)
  - `subscribe(array $tokenIds): void` — adds tokens to active subscription mid-connection
  - Parses incoming messages:
    - `price_change` events → `onPriceUpdate` callback
    - `market_result` events → `onMarketResolved` callback
    - `book` events → ignored (no orderbook recording needed)

#### 3d. OracleFeedService
- [ ] Create `app/Recorder/OracleFeedService.php`
  - Manages the Chainlink RTDS WebSocket connection (single shared connection for all assets)
  - WS URL: `wss://ws-live-data.polymarket.com` (or `RTDS_WS_URL` env override)
  - On connect: subscribes to `crypto_prices_chainlink` channel for all configured assets
  - Parses incoming price messages, filters by configured asset symbols
  - `connect(LoopInterface $loop, callable $onTick): void`
  - Reconnects on stale data (no tick for 30s)

#### 3e. RecorderState (Redis status)
- [ ] Create `app/Recorder/RecorderState.php`
  - Thin wrapper around Redis for storing/reading live recorder status
  - `update(array $stats): void` — JSON-encodes and writes to `recorder:status` Redis key (TTL 120s)
  - `get(): array` — reads + decodes; returns default "offline" shape if key missing/expired
  - Stats shape:
    ```php
    [
        'running'        => true,
        'started_at'     => timestamp,
        'oracle'         => ['btc' => [...last tick...], 'eth' => [...], 'sol' => [...]],
        'clob'           => ['connected' => bool, 'subscribed' => int, 'snapshots_written' => int],
        'markets'        => ['total' => int, 'active' => int],
        'candles_written'=> int,
        'last_updated'   => timestamp,
    ]
    ```

### Phase 4 — Artisan command: `recorder:start`

- [ ] Create `app/Console/Commands/RecorderCommand.php` (signature: `recorder:start`)
  - Boots ReactPHP event loop
  - Instantiates all services
  - Wires them together:
    1. Start Gamma poller (every 20s timer) → discovers windows, collects token IDs
    2. Start CLOB feed → subscribe to discovered token IDs
       - On price update: buffer `ClobRow[]`; flush to `clob_snapshots` every 1s in a transaction
       - On market resolved: `DB::table('windows')->where('condition_id', ...)->update(['outcome' => ...])`
    3. Start Oracle feed → on tick: insert `oracle_ticks` + call `CandleService::tick()`
       - On candle close: insert into `candles_1m`
    4. Status update timer (every 5s): write stats to `RecorderState`
  - Handles SIGTERM / SIGINT: flush buffers, stop loop cleanly
  - Logs to stdout (supervisor captures it)
- [ ] Register command in `app/Console/Kernel.php` or `routes/console.php`

### Phase 5 — Supervisor: replace ingest-sync with recorder

- [ ] Edit `docker/supervisord.conf`:
  - Remove `[program:ingest-sync]`
  - Add:
    ```ini
    [program:recorder]
    command=php /var/www/html/artisan recorder:start
    user=sail
    stdout_logfile=/dev/stdout
    stdout_logfile_maxbytes=0
    stderr_logfile=/dev/stderr
    stderr_logfile_maxbytes=0
    autostart=true
    autorestart=true
    stopwaitsecs=10
    ```
- [ ] Rebuild + redeploy: `docker compose up -d --build`
- [ ] Verify recorder is running: `docker compose exec app supervisorctl status`
- [ ] Verify data flowing: `docker compose exec app php artisan tinker` → check row counts

### Phase 6 — Admin dashboard

#### 6a. Status API endpoint
- [ ] Create `app/Http/Controllers/Web/RecorderController.php`
  - `status()` action: returns `RecorderState::get()` as JSON
  - Protected by admin middleware (see below)

#### 6b. Admin middleware
- [ ] Create `app/Http/Middleware/EnsureAdmin.php`
  - Checks `auth()->user()->role === 'admin'` (or a simple `is_admin` bool column)
  - Redirects to `/login` if not authed, 403 if authed but not admin
- [ ] Add `is_admin` boolean column to `users` table via migration (default false; seed the first user as admin)
- [ ] Register middleware alias in `bootstrap/app.php`

#### 6c. Blade view
- [ ] Create `resources/views/admin/recorder.blade.php`
  - Extends main layout
  - Shows: connection status (CLOB / RTDS), active markets list, row counts per table, last tick timestamps
  - Auto-refreshes via `setInterval` polling `/admin/recorder/status` every 3s (plain JS fetch + DOM update)
  - No frontend build step needed — vanilla JS, Tailwind classes (already in the project)

#### 6d. Routes
- [ ] Add to `routes/web.php`:
  ```php
  Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
      Route::get('/recorder', [RecorderController::class, 'index'])->name('admin.recorder');
      Route::get('/recorder/status', [RecorderController::class, 'status'])->name('admin.recorder.status');
  });
  ```

### Phase 7 — Infrastructure cleanup

- [ ] Remove `recorder.fullstacklabs.org` ingress from `~/.cloudflared/config.yml`
- [ ] Copy updated config to `/etc/cloudflared/config.yml`
- [ ] Restart cloudflared: `sudo systemctl restart cloudflared`
- [ ] Remove CNAME for `recorder` in Cloudflare DNS dashboard
- [ ] Update `CLAUDE.md` (this server's brain):
  - Remove port 3007 from port map
  - Remove `market-recorder` from project registry
  - Remove `recorder.fullstacklabs.org` from public URLs
  - Add note: recorder is now built into `polymarket-data-service`
- [ ] Optionally archive the `market-recorder` repo (don't delete — it has the JSONL recording logic if ever needed again)

### Phase 8 — JSONL backfill (optional, do later)

The old recorder saved compressed `.jsonl.gz` files to `~/projects/market-recorder/data/recordings/`.
These can be backfilled into PostgreSQL using the existing `BackfillRecordingsCommand` if desired.
Not blocking — do after the live recorder is proven stable.

---

## Key implementation notes

### Slug generation for market discovery
Window open times are aligned to the duration boundary:
```
5m  windows: open_ts = floor(now / 300) * 300  (seconds)
15m windows: open_ts = floor(now / 900) * 900
```
Generate slugs for: previous window, current window, next 2 windows.
Refresh every 20s so upcoming windows are cached before they go live.

### CLOB message format (Polymarket docs)
- Subscribe:   `{"type":"market","assets_ids":["0x..."],"custom_feature_enabled":true}`
- Mid-connect: `{"operation":"subscribe","assets_ids":["0x..."]}`
- Keepalive:   send text `"PING"` every 10s, server replies text `"PONG"`
- Price event: `{"event_type":"price_change","asset_id":"0x...","price":"0.86",...}`
- Resolved:    `{"event_type":"market_result","market":"0x...","outcome":"YES"}`

### RTDS message format (Chainlink)
- Subscribe: `{"type":"subscribe","channel":"crypto_prices_chainlink","data":{"symbol":"btc/usd"}}`
- Tick:      `{"type":"event","channel":"crypto_prices_chainlink","data":{"symbol":"btc/usd","price":"84500.00","timestamp":...}}`
- Filter client-side by asset symbol

### Batching strategy (same as old recorder)
- Oracle ticks: insert immediately (low volume, ~1/s per asset)
- CLOB snapshots: buffer up to 100 rows, flush every 1s in a single transaction (high volume)
- Candles: insert on close (1 per asset per minute)

### Reconnect logic
- Both WebSockets use exponential backoff (1s → 2s → 4s → max 30s)
- Oracle feed: reconnect if no tick received for 30s
- CLOB feed: reconnect if PONG not received within 30s of a PING

---

## Files to delete (after Phase 0)
- `app/Services/Ingest/SqliteSyncService.php`
- `app/Console/Commands/IngestSyncCommand.php`

## Files to create
- `app/Recorder/AssetConfig.php`
- `app/Recorder/CandleService.php`
- `app/Recorder/MarketDiscoveryService.php`
- `app/Recorder/ClobFeedService.php`
- `app/Recorder/OracleFeedService.php`
- `app/Recorder/RecorderState.php`
- `app/Console/Commands/RecorderCommand.php`
- `app/Http/Controllers/Web/RecorderController.php`
- `app/Http/Middleware/EnsureAdmin.php`
- `database/migrations/XXXX_add_is_admin_to_users_table.php`
- `resources/views/admin/recorder.blade.php`
