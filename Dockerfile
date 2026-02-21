FROM php:8.2-apache

# 1. Install PostgreSQL dependencies
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# 2. Badilisha DocumentRoot ya Apache iwe folder la /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Copy kodi zako zote (pamoja na folder la public)
COPY . /var/www/html/

# 4. Ruhusu Apache kusoma files
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
