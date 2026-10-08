FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip libzip-dev curl \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html/vengg3/backend
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY . .

RUN sed -ri 's!/var/www/html!/var/www/html/vengg3/backend/public!g' /etc/apache2/sites-available/000-default.conf \
    && mkdir -p public/uploads/avatars public/uploads/backups src/Config \
    && mkdir -p storage/document-templates storage/security \
    && chown -R www-data:www-data public/uploads src/Config storage \
    && printf '%s\n' '<Directory /var/www/html/vengg3/backend/public>' 'AllowOverride All' '</Directory>' > /etc/apache2/conf-available/vengg-security.conf \
    && a2enmod headers && a2enconf vengg-security \
    && printf '%s\n' 'ServerTokens Prod' 'ServerSignature Off' > /etc/apache2/conf-available/vengg-server-info.conf \
    && a2enconf vengg-server-info
