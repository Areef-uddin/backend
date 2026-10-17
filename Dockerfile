FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev ca-certificates \
    && docker-php-ext-install pdo_mysql bcmath intl zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN echo "upload_max_filesize = 50M\npost_max_size = 50M\nmemory_limit = 256M\nmax_execution_time = 300\n" > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN sed -ri \
    -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Enable .htaccess rewrite rules for Laravel routing
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/app/public/joining_docs public/storage/joining_docs bootstrap/cache \
    && chown -R www-data:www-data storage public/storage bootstrap/cache \
    && chmod -R 775 storage public/storage bootstrap/cache

EXPOSE 80

CMD ["sh", "-c", "php artisan storage:link || true; php artisan migrate --force || true; php artisan package:discover --ansi || true; apache2-foreground"]
