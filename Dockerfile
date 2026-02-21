FROM php:8.2-apache

# Install PostgreSQL extensions kwa ajili ya PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copy kodi zako zote ziingie kwenye server
COPY . /var/www/html/

# Ruhusu Apache iweze kusoma mafaili
RUN chown -R www-data:www-data /var/www/html
