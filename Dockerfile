# TextileCycle — image de production (PHP 8.2 + Apache)
# Construite et publiée sur Docker Hub par le pipeline GitHub Actions (étape CD).

# ── Étape 1 : dépendances PHP (sans les outils de développement) ──
FROM composer:2 AS dependances
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ── Étape 2 : image finale ──
FROM php:8.2-apache

# Extensions PHP utilisées par l'application (MySQL, images, archives).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip exif bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache sert le dossier public/ de Laravel, avec la réécriture d'URL.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && a2enmod rewrite headers

WORKDIR /var/www/html
COPY --from=dependances /app /var/www/html

# Laravel doit pouvoir écrire dans storage/ et bootstrap/cache/.
RUN php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
