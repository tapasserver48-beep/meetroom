# Use official PHP image with extensions
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    linux-headers \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    sqlite-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    gettext

# Configure GD with freetype and jpeg
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install PHP extensions one by one to isolate failures
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install zip
RUN docker-php-ext-install gd
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install xml
RUN docker-php-ext-install bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Copy artisan and essential files for package discovery
COPY artisan ./
COPY bootstrap/app.php ./bootstrap/app.php
COPY config/app.php ./config/app.php

# Debug: verify files exist
RUN ls -la artisan bootstrap/app.php config/app.php && chmod +x artisan && php -v

# Install PHP dependencies (with scripts for package discovery)
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-dev --no-interaction

# Test PHP and artisan
RUN php -r "echo 'PHP OK';" && php artisan --version

# Copy package files
COPY package.json package-lock.json ./

# Install and build frontend assets
RUN npm ci && npm run build

# Copy application code
COPY . .

# Create SQLite database directory and file
RUN mkdir -p database && touch database/database.sqlite && chmod 664 database/database.sqlite

# Run Laravel build commands
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Copy nginx config template and entrypoint
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port (Render uses PORT env var, default 10000)
EXPOSE 10000

# Start services
ENTRYPOINT ["/entrypoint.sh"]