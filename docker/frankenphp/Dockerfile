FROM dunglas/frankenphp:php8.3-alpine as base

ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime

WORKDIR /app

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        intl \
        opcache


# Create log directories
RUN mkdir -p /var/log/caddy /var/log/php \
    && chown -R www-data:www-data /var/log/caddy /var/log/php

# PHP settings for production
RUN printf 'opcache.enable=1\n\
opcache.memory_consumption=256\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=0\n\
opcache.save_comments=1\n\
opcache.fast_shutdown=1\n\
realpath_cache_size=4096K\n\
realpath_cache_ttl=600\n' > /usr/local/etc/php/conf.d/opcache.ini

# Expose ports
EXPOSE 80 443 2019

# Development image
FROM base as dev

# Composer version
ENV COMPOSER_VERSION=2.8.12

# Install Composer
RUN apk add --no-cache \
    git \
    && curl -sS https://getcomposer.org/installer | php -- \
    --version=$COMPOSER_VERSION \
    --install-dir=/usr/local/bin \
    --filename=composer \
    && composer config -g secure-http false

# PHP settings for development
RUN printf 'memory_limit = 256M\n\
display_errors = On\n\
error_reporting = E_ALL\n\
log_errors = On\n\
error_log = /dev/stderr\n\
opcache.validate_timestamps = 1\n\
opcache.revalidate_freq = 0\n\
xdebug.mode = develop\n\
allow_url_fopen = On\n' > /usr/local/etc/php/conf.d/dev.ini

# Production image
FROM base as prod

# Copy only necessary files
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy source code
COPY . .

# Copy production Caddyfile
COPY docker/frankenphp/Caddyfile.prod /etc/caddy/Caddyfile

# Execute production build tasks
RUN composer dump-autoload --optimize --no-dev \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

# Set correct permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && chmod -R 775 /app/var

# Switch to www-data user
USER www-data

# Default startup command
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1
