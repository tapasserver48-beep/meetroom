#!/bin/sh

# Wait for database if using MySQL
# (SQLite doesn't need this)

# Create SQLite database if it doesn't exist
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chmod 664 /var/www/html/database/database.sqlite

# Ensure storage and bootstrap/cache directories exist and are writable
mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate APP_KEY if not set or invalid
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
else
    # Validate APP_KEY format (must be base64: + 32 bytes = 44 chars after base64:)
    KEY_CHECK=$(php -r "
        \$key = getenv('APP_KEY');
        if (str_starts_with(\$key, 'base64:')) {
            \$decoded = base64_decode(substr(\$key, 7), true);
            if (\$decoded !== false && strlen(\$decoded) === 32) {
                echo 'valid';
            } else {
                echo 'invalid';
            }
        } else {
            echo 'invalid';
        }
    ")
    if [ "$KEY_CHECK" != "valid" ]; then
        echo "Invalid APP_KEY format, generating new one..."
        php artisan key:generate --force
    fi
fi

# Create .env from environment variables (Render injects these at runtime)
# This ensures Render dashboard env vars take precedence over .env.example
cat > /var/www/html/.env <<EOF
APP_NAME=MeetRoom
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}

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
REVERB_HOST=${REVERB_HOST:-0.0.0.0}
REVERB_PORT=${REVERB_PORT:-8080}
REVERB_SCHEME=${REVERB_SCHEME:-http}
REVERB_PUBLIC_WS_URL=${REVERB_PUBLIC_WS_URL:-}

CLOUDFLARE_TURN_KEY_ID=${CLOUDFLARE_TURN_KEY_ID:-}
CLOUDFLARE_TURN_KEY_SECRET=${CLOUDFLARE_TURN_KEY_SECRET:-}

LOG_CHANNEL=${LOG_CHANNEL:-stderr}
EOF

# Clear any cached config before regenerating
php artisan config:clear 2>/dev/null || true

# Run migrations
php artisan migrate --force

# Clear and cache configs
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

# Start Nginx in foreground
nginx -g "daemon off;"