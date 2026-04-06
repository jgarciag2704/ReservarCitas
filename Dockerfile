FROM php:8.2-apache

COPY . /var/www/html/

RUN docker-php-ext-install pdo pdo_mysql

# Habilitar rewrite
RUN a2enmod rewrite

# 🔥 ELIMINAR TODOS LOS MPM POSIBLES
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load && \
    rm -f /etc/apache2/mods-available/mpm_*.load

# 🔥 HABILITAR SOLO PREFORK
RUN a2enmod mpm_prefork

# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80