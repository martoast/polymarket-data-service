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

echo "==> Configuring recorder (RECORDER_ENABLED=${RECORDER_ENABLED:-false})..."
if [ "${RECORDER_ENABLED:-false}" = "true" ]; then
    sed -i 's/^autostart=false$/autostart=true/' /etc/supervisor/conf.d/supervisord.conf
    echo "    Recorder: ON"
else
    # Disable only the recorder process
    python3 -c "
import re, sys
conf = open('/etc/supervisor/conf.d/supervisord.conf').read()
conf = re.sub(r'(\[program:recorder\].*?autostart=)true', r'\1false', conf, flags=re.DOTALL)
open('/etc/supervisor/conf.d/supervisord.conf', 'w').write(conf)
"
    echo "    Recorder: OFF (set RECORDER_ENABLED=true to enable)"
fi

echo "==> Starting supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
