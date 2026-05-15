FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    linux-headers \
    build-base \
    git \
    unzip \
    libzip-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo pdo_mysql zip

RUN pecl install redis xdebug && docker-php-ext-enable redis xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/html