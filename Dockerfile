FROM php:8.2-cli

# Instalar extensiones
RUN docker-php-ext-install pdo pdo_mysql

# Copiar proyecto
WORKDIR /app
COPY . .

# Exponer puerto dinámico de Railway
EXPOSE 8080

# Ejecutar servidor PHP
CMD php -S 0.0.0.0:$PORT -t public