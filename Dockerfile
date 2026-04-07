FROM php:8.2-fpm

# Instalar nginx
RUN apt-get update && apt-get install -y nginx

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql

# Copiar proyecto
WORKDIR /var/www/html
COPY . .

# Copiar config de nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 8080

# Comando de arranque
CMD service nginx start && php-fpm