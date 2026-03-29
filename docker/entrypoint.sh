#!/bin/bash
set -e

echo "==> Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding defaults (idempotent)..."
php artisan db:seed --force 2>/dev/null || true

echo "==> Starting supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
