FROM php:8.4-fpm-alpine
RUN apk add --no-cache nginx libpq postgresql-dev && docker-php-ext-install pdo_pgsql && apk del postgresql-dev
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative && mkdir -p var /run/nginx && chown -R www-data:www-data var
ENV APP_ENV=prod APP_DEBUG=0
CMD ["sh", "bin/start"]
