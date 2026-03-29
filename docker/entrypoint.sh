#!/bin/bash
set -e

echo "==> Clearing stale bootstrap caches..."
rm -f bootstrap/cache/config.php bootstrap/cache/routes*.php \
      bootstrap/cache/services.php bootstrap/cache/packages.php \
      bootstrap/cache/events.php

echo "==> Caching config / routes / views..."
php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding defaults (idempotent)..."
php artisan db:seed --force 2>/dev/null || true

echo "==> Starting supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
