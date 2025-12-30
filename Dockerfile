FROM php:8.2-apache

# Install PHP extensions voor phpBB
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libzip-dev unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) gd intl mysqli pdo pdo_mysql zip \
 && a2enmod rewrite \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Kopieer Apache vhost en PHP ini
COPY apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY php/php.ini /usr/local/etc/php/conf.d/phpbb.ini

# Zet juiste permissies
RUN chown -R www-data:www-data /var/www/html

