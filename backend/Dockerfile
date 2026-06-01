FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    postgresql-client \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    nginx \
    supervisor \
    curl \
    linux-headers \
    autoconf \
    g++ \
    make \
    git

RUN docker-php-ext-install \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    sockets \
    pcntl

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/log/supervisor /var/run

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# --- dev stage ---
FROM base AS dev
COPY docker/php/opcache.dev.ini /usr/local/etc/php/conf.d/opcache.ini
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-interaction
COPY . .
RUN composer dump-autoload --optimize
RUN mkdir -p var && chown -R www-data:www-data var/
EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# --- prod stage ---
FROM base AS prod
ENV APP_ENV=prod
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-dev
RUN mkdir -p var && chown -R www-data:www-data var/
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
