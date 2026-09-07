FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libpq-dev \
    && docker-php-ext-install pdo_sqlite pdo_mysql pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs database \
    && chown -R www-data:www-data storage database \
    && chmod +x docker-entrypoint.sh \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
ENTRYPOINT ["./docker-entrypoint.sh"]
CMD ["apache2-foreground"]
