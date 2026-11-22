# Innes Eye & Visioncare Centre – production image (PHP 8.3 + Apache).
# The built CSS/JS are committed in public/build, so Node.js is not needed.

# ---- Stage 1: PHP packages (vendor/) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs

# ---- Stage 2: the web server ----
FROM php:8.3-apache

# PHP extensions used by the site (MySQL, image processing, translations, uploads).
RUN apt-get update \
 && apt-get install -y --no-install-recommends libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip exif bcmath opcache \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache: serve only public/, read Laravel's .htaccess, no directory listings.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && a2enmod rewrite headers \
 && printf '%s\n' 'ServerName localhost' 'ServerTokens Prod' 'ServerSignature Off' \
      '<Directory /var/www/html/public>' '    Options -Indexes +FollowSymLinks' '    AllowOverride All' '    Require all granted' '</Directory>' \
      > /etc/apache2/conf-available/innes.conf \
 && a2enconf innes

COPY docker/php.ini /usr/local/etc/php/conf.d/innes.ini
RUN sed -i 's/\r$//' /usr/local/etc/php/conf.d/innes.ini
COPY --from=vendor /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
 && php artisan package:discover --ansi \
 && rm -f .htaccess \
 && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/innes-entrypoint
# Strip Windows line endings in case the file was checked out on Windows.
RUN sed -i 's/\r$//' /usr/local/bin/innes-entrypoint && chmod +x /usr/local/bin/innes-entrypoint

EXPOSE 80
ENTRYPOINT ["innes-entrypoint"]
CMD ["apache2-foreground"]
