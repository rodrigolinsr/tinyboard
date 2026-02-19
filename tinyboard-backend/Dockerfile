FROM php:8.5-cli-alpine

RUN apk add --no-cache \
        icu-dev \
        sqlite \
        sqlite-dev \
        oniguruma-dev \
        libzip-dev \
        $PHPIZE_DEPS

RUN docker-php-ext-install \
        pdo \
        pdo_sqlite \
        intl \
        mbstring \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
