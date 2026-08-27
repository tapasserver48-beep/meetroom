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

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
fi

# Copy .env.example to .env if .env doesn't exist (for any missing env vars)
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

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