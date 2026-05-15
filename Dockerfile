FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    linux-headers \
    build-base \
    git \
    unzip \
    libzip-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo pdo_mysql zip

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html