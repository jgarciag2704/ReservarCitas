FROM php:8.2-fpm

RUN apt-get update && apt-get install -y nginx

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY . .

COPY nginx.conf /etc/nginx/sites-available/default

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
 && ln -sf /dev/stderr /var/log/nginx/error.log
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]