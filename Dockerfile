# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine AS base

# System deps + PHP extensions needed for Laravel + MySQL + Redis
RUN apk add --no-cache \
        git curl unzip icu-dev libzip-dev oniguruma-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev autoconf g++ make linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath intl zip gd opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make linux-headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Sensible PHP defaults for production-ish containers
RUN { \
  echo "memory_limit=512M"; \
        echo "upload_max_filesize=20M"; \
        echo "post_max_size=20M"; \
echo "opcache.enable=1"; \
        echo "opcache.enable_cli=0"; \
 echo "opcache.validate_timestamps=1"; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

# App source is bind-mounted in dev via docker-compose; copy for standalone builds
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]
