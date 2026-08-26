#!/bin/sh

# Wait for database if using MySQL
# (SQLite doesn't need this)

# Run migrations
php artisan migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start Reverb WebSocket server in background
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Start Nginx in foreground
nginx -g "daemon off;"