FROM php:8.2-apache

COPY . /var/www/html/

RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite

# Debug: listar módulos cargados
RUN apache2ctl -M

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80