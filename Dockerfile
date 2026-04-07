FROM php:8.2-apache

# Instalar extensiones
RUN docker-php-ext-install pdo pdo_mysql

# Activar mod_rewrite
RUN a2enmod rewrite

# FORZAR solo prefork (esto es la clave)
RUN a2dismod --force mpm_event mpm_worker && \
    a2enmod mpm_prefork

# Copiar proyecto
COPY . /var/www/html/

# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Permisos (recomendado)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]