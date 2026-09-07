#!/bin/sh
set -e
cd /var/www/html
[ -f .env ] || cp .env.example .env
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs database
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null && [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi
php artisan migrate --force
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then php artisan autoevolve:install --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"; fi
php artisan config:cache || true
php artisan route:cache || true
exec "$@"
