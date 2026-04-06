FROM php:8.2-apache

COPY . /var/www/html/

RUN docker-php-ext-install pdo pdo_mysql

# Modulos Apache
RUN a2enmod rewrite

# Forzar solo un MPM
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork

# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80