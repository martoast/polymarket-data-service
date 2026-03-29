# polymarket-data-service — Claude Code Instructions

## Stack
- **Laravel 12** — API + Blade web views in the same project
- **PHP 8.4**, PostgreSQL 16 + TimescaleDB, Redis 7
- **Laravel Sanctum** — Bearer token auth (API) + session auth (web)
- **Laravel Cashier** — Stripe billing
- **Supervisor** — manages PHP serve, queue worker, scheduler, ingest-sync inside the container
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
# Edit .env — fill in APP_KEY, DB_PASSWORD, STRIPE_*, MAILGUN_SECRET, RECORDER_SQLITE_FILE

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
| `polymarket-pgsql` | timescale/timescaledb:latest-pg16 | Database |
| `polymarket-redis` | redis:7-alpine | Cache + queues |

### What supervisor runs inside the app container
| Process | Command | Purpose |
|---|---|---|
| `php` | `artisan serve --host=0.0.0.0 --port=80` | HTTP |
| `queue-worker` | `artisan queue:work` | Jobs (ingest, resolution, etc.) |
| `scheduler` | `artisan schedule:run` every 60s | Cron tasks |
| `ingest-sync` | `artisan ingest:sync --once` every 30s | Recorder SQLite → DB |

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

### Recorder SQLite integration
The recorder writes to a SQLite file. The app container reads it via the `recorder_data` Docker volume mounted at `/var/data`. Set in `.env`:
```
RECORDER_SQLITE_FILE=/var/data/polymarket.db
```
If the recorder runs on the host (not in Docker), bind-mount the file instead:
```yaml
# in docker-compose.yml under app.volumes:
- /path/to/recorder/data/polymarket.db:/var/data/polymarket.db:ro
```

### Useful commands
```bash
# View all logs
docker compose logs -f app

# Run artisan commands
docker compose exec app php artisan tinker
docker compose exec app php artisan ingest:sync --once
docker compose exec app php artisan backfill:recordings --path=/var/data/recordings

# Restart a single supervisor process
docker compose exec app supervisorctl restart ingest-sync

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
RECORDER_SQLITE_FILE=/var/data/polymarket.db
```

## Key architecture notes
- `ForceJsonResponse` middleware is **API-only** — web routes return HTML
- Exception handlers in `bootstrap/app.php` check `$request->expectsJson()` before returning JSON vs redirect
- Email verification is required before users can hit any `/api/v1/*` endpoint (`verified` middleware)
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
