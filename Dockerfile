FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" intl mbstring pgsql pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

COPY . .
COPY docker/tienda-abarrotes.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start.sh /usr/local/bin/tienda-abarrotes-start
RUN chmod +x /usr/local/bin/tienda-abarrotes-start \
    && mkdir -p writable/cache writable/debugbar writable/logs writable/session writable/uploads/clientes writable/uploads/comprobantes public/assets/img/productos \
    && chown -R www-data:www-data writable public/assets/img/productos

EXPOSE 10000
CMD ["/usr/local/bin/tienda-abarrotes-start"]
