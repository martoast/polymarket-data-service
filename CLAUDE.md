# polymarket-data-service — Claude Code Instructions

## Stack
- **Laravel 12** — API + Blade web views in the same project
- **PHP 8.4**, PostgreSQL 16 + TimescaleDB, Redis 7
- **Laravel Sanctum** — Bearer token auth (API) + session auth (web)
- **Laravel Cashier** — Stripe billing
- **ReactPHP + ratchet/pawl** — long-running WebSocket recorder (`recorder:start`)
- **Supervisor** — manages PHP serve, queue worker, scheduler, and recorder inside the container
- **Mailgun** — transactional email via `mg.fullstacklabs.org`

## Local dev
```bash
# Dependencies
composer install

# SQLite for local (already set in .env)
php artisan migrate
php artisan db:seed --class=AssetSeeder

# Serve
php artisan serve --port=8001
php artisan queue:work --queue=ingest,resolution,high,default
```

Local `.env` uses `DB_CONNECTION=sqlite`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`. Do not change these for local dev.

## Docker deployment (home server)

### First deploy
```bash
# 1. Copy and fill in secrets
cp .env.example .env
# Edit .env — fill in APP_KEY, DB_PASSWORD, STRIPE_*, MAILGUN_SECRET

# 2. Generate app key if blank
docker compose run --rm app php artisan key:generate --show
# Paste output into .env as APP_KEY=

# 3. Build and start
docker compose up -d --build
```

### What docker compose starts
| Container | Image | Purpose |
|---|---|---|
| `polymarket-app` | built from Dockerfile | Laravel app on port 8001 |
| `polymarket-recorder` | same image | Crypto market recorder (`recorder:start`) |
| `polymarket-recorder-weather` | same image | Weather market recorder (`weather:start`) |
| `polymarket-pgsql` | timescale/timescaledb:latest-pg16 | Database |
| `polymarket-redis` | redis:7-alpine | Cache + queues |

### What supervisor runs inside the app container
| Process | Command | Purpose |
|---|---|---|
| `php` | `artisan serve --host=0.0.0.0 --port=80` | HTTP |
| `queue-worker` | `artisan queue:work` | Jobs |
| `scheduler` | `artisan schedule:run` every 60s | Cron tasks |

### On every container start (entrypoint.sh)
1. `php artisan config:cache`
2. `php artisan route:cache`
3. `php artisan view:cache`
4. `php artisan migrate --force`
5. `php artisan db:seed --force` (idempotent — seeds assets + test users with api_keys)
6. Supervisord starts

### Subsequent deploys
```bash
git pull
docker compose up -d --build
```
Migrations and cache busting run automatically via entrypoint.sh.

### Useful commands
```bash
# View all logs
docker compose logs -f app

# Restart the recorder process (supervisorctl has no socket — kill it and supervisord restarts it)
docker compose exec app pkill -f 'recorder:start'

# Run backfill manually (fills break_price_usd from oracle_ticks + outcomes from Gamma)
docker compose exec app php artisan recorder:backfill-windows

# Check supervisor status
docker compose exec app supervisorctl status

# Rebuild without cache
docker compose build --no-cache app
```

### Production .env differences from local
```
DB_CONNECTION=pgsql
DB_HOST=pgsql
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

## Recorder architecture

The recorder runs as a persistent Laravel artisan command (`recorder:start`) using ReactPHP's event loop. No separate process or service needed.

### Data flow — Crypto recorder (`recorder:start`)
```
Chainlink RTDS WS (wss://ws-live-data.polymarket.com)
  → OracleFeedService → oracle_ticks + CandleService → candles_1m

Polymarket CLOB WS (wss://ws-subscriptions-clob.polymarket.com/ws/market)
  → ClobFeedService → clob_snapshots (flushed every 1s)
  → resolution events → markets.outcome updated

Gamma API (https://gamma-api.polymarket.com)
  → MarketDiscoveryService (every 20s) → markets table upserted (category=crypto)
  → break_value set from oracle_ticks at open_ts
```

### Data flow — Weather recorder (`weather:start`)
```
Open-Meteo API (https://api.open-meteo.com) — free, no key
  → OpenMeteoService (every 5 min) → weather_readings table
  → tracks running daily max in memory, resets at local midnight

Polymarket CLOB WS (same protocol as crypto)
  → ClobFeedService → clob_snapshots (flushed every 1s)
  → resolution events → markets.outcome updated

Gamma API
  → WeatherMarketDiscoveryService (every 20s) → markets table upserted (category=weather)
  → generates date-based slugs: "highest-temperature-in-tokyo-on-april-3-2026"
  → parses temperature bracket from question → break_value in Celsius
```

### Key files
| File | Purpose |
|---|---|
| `app/Console/Commands/RecorderCommand.php` | Crypto recorder — Chainlink + CLOB + Gamma |
| `app/Console/Commands/WeatherRecorderCommand.php` | Weather recorder — Open-Meteo + CLOB + Gamma |
| `app/Recorder/OracleFeedService.php` | Chainlink RTDS WebSocket |
| `app/Recorder/ClobFeedService.php` | Polymarket CLOB WebSocket (shared by both recorders) |
| `app/Recorder/MarketDiscoveryService.php` | Gamma API discovery for crypto markets |
| `app/Recorder/Weather/WeatherMarketDiscoveryService.php` | Gamma API discovery for weather markets |
| `app/Recorder/Weather/OpenMeteoService.php` | Open-Meteo HTTP poller |
| `app/Recorder/CandleService.php` | In-memory 1m OHLCV candle aggregation |
| `app/Recorder/RecorderState.php` | Writes crypto recorder stats to Redis `recorder:status` |
| `app/Recorder/AssetConfig.php` | BTC/ETH/SOL config — slug prefixes, Chainlink symbols |

### RTDS subscription format (critical — wrong format = silent no-data)
```json
{"action":"subscribe","subscriptions":[{"topic":"crypto_prices_chainlink","type":"*","filters":""}]}
```
Messages arrive as: `{"topic":"crypto_prices_chainlink","type":"update","payload":{"symbol":"btc/usd","value":66877.11,"timestamp":1774756878000},...}`

### Admin dashboard
`/admin/recorder` — live status page (polls every 3s). Requires `is_admin = true` on the user.

### Data quality rules
- `GET /windows` only returns windows where `break_price_usd > 0` AND (`outcome` is not null OR `close_ts` is in the future)
- `recorder:backfill-windows` runs every 5 minutes via scheduler to resolve any windows that expired between CLOB events

## Key architecture notes
- `ForceJsonResponse` middleware is **API-only** — web routes return HTML
- Exception handlers in `bootstrap/app.php` check `$request->expectsJson()` before returning JSON vs redirect
- Email verification is required before users can hit any `/api/v1/*` endpoint (`verified` middleware)
- `trustProxies(at: '*')` is set in `bootstrap/app.php` — required because Cloudflare terminates SSL; without it signed URLs (email verification) break with 403 Invalid Signature
- **API key provisioning**: the plain Sanctum token is stored in `users.api_key` so it's always visible on the dashboard — no "show once" session flash. On login/register, existing tokens are revoked and a new one is created + saved. Regenerate button does the same on demand.
- Tier logic lives in `app/Models/User.php` — `dailyRateLimit()` and `historyLimitDays()`
- Rate limiter is defined in `app/Providers/AppServiceProvider.php` as `api-tier`

## Stripe local testing (webhook forwarding)

Stripe webhooks can't reach localhost directly — use the Stripe CLI to forward them.

```bash
# 1. Login (one-time)
stripe login

# 2. Forward events to the local app — this prints a whsec_ secret, paste it into .env as STRIPE_WEBHOOK_SECRET
stripe listen --forward-to localhost:8001/api/webhooks/stripe

# 3. Trigger a test event in a separate terminal
stripe trigger payment_intent.succeeded
```

Other useful triggers:
```bash
stripe trigger customer.subscription.created
stripe trigger customer.subscription.updated
stripe trigger customer.subscription.deleted
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed
```

The `whsec_` secret printed by `stripe listen` is only valid for that CLI session — it changes each time you run it. In production use the permanent webhook secret from the Stripe dashboard.

## Mail
- Provider: Mailgun
- Domain: `mg.fullstacklabs.org`
- From: `noreply@fullstacklabs.org`
- Endpoint: `api.mailgun.net` (US region)
- Secret: set `MAILGUN_SECRET` in `.env` — never commit it
