#!/bin/sh

# Ensure ALL storage and cache directories exist with full permissions
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache

# Write .env FIRST so Laravel commands can read it
cat > /var/www/html/.env <<EOF
APP_NAME=MeetRoom
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-reverb}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

REVERB_APP_ID=${REVERB_APP_ID:-local}
REVERB_APP_KEY=${REVERB_APP_KEY:-local}
REVERB_APP_SECRET=${REVERB_APP_SECRET:-local}
REVERB_HOST=${REVERB_HOST:-127.0.0.1}
REVERB_PORT=${REVERB_PORT:-8080}
REVERB_SCHEME=${REVERB_SCHEME:-http}
REVERB_PUBLIC_WS_URL=${REVERB_PUBLIC_WS_URL:-}

CLOUDFLARE_TURN_KEY_ID=${CLOUDFLARE_TURN_KEY_ID:-}
CLOUDFLARE_TURN_KEY_SECRET=${CLOUDFLARE_TURN_KEY_SECRET:-}

LOG_CHANNEL=${LOG_CHANNEL:-stderr}
EOF

# Now validate and fix APP_KEY if needed
php artisan key:generate --force

# Clear all cached config (stale cache causes 500)
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Run migrations
php artisan migrate --force

# Cache everything for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate nginx config from template using PORT env var (default 10000)
export PORT=${PORT:-10000}
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Start PHP-FPM in background
php-fpm -D

# Start Reverb WebSocket server in background
php artisan reverb:start --host=0.0.0.0 --port=8080 &
sleep 2

# Start Nginx in foreground
nginx -g "daemon off;"
