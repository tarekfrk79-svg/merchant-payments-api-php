FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev unzip && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative && a2enmod rewrite && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && mkdir -p var && chown -R www-data:www-data var
ENV APP_ENV=prod APP_DEBUG=0
CMD ["bash", "bin/start"]
