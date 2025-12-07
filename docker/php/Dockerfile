# /docker/php/Dockerfile
FROM php:8.3-fpm

# system deps
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev libxml2-dev zlib1g-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www/html

# user
RUN useradd -G www-data,root -u 1000 -m symfony

USER symfony
