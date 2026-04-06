FROM php:8.2-apache

COPY . /var/www/html/

RUN docker-php-ext-install pdo pdo_mysql

# Habilitar rewrite
RUN a2enmod rewrite

# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80