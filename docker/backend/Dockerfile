FROM php:8.4-fpm-alpine

# Системные зависимости
# postgresql-dev — нужен для pdo_pgsql/pgsql на Alpine
RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    rabbitmq-c-dev \
    linux-headers \
    bash \
    $PHPIZE_DEPS

# PHP расширения: основные
# ctype, iconv, session, tokenizer, simplexml — уже встроены в PHP 8.4, устанавливать не нужно
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        zip \
        gd \
        pcntl \
        sockets

# PHP расширения: PECL (redis, amqp, xdebug)
RUN pecl install redis amqp xdebug \
    && docker-php-ext-enable redis amqp xdebug

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Пользователь www
RUN addgroup -g 1000 www \
    && adduser -u 1000 -G www -s /bin/bash -D www

WORKDIR /var/www/app

# Права на директории, которые потребует Symfony
RUN mkdir -p var/cache var/log var/logNginx \
    && chown -R www:www /var/www/app

USER www

EXPOSE 9000

CMD ["php-fpm"]
