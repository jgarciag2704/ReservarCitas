FROM php:8.2-fpm

RUN apt-get update && apt-get install -y nginx

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY . .

# Copiar template nginx
COPY nginx.conf /etc/nginx/templates/default.conf.template

# Logs visibles
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
 && ln -sf /dev/stderr /var/log/nginx/error.log

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

CMD ["sh", "-c", "envsubst '$PORT' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf && php-fpm & nginx -g 'daemon off;'"]