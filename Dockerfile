# Dockerfile

FROM php:8.3-fpm

# 1) Instala dependencias del sistema
RUN apt-get update \
  && apt-get install -y \
       git \
       curl \
       zip \
       unzip \
       libpng-dev \
       libonig-dev \
       libxml2-dev \
       libzip-dev \
       default-mysql-client \
       libmariadb-dev \
  && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
  && rm -rf /var/lib/apt/lists/*

# 2) Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 3) Copia sólo composer.json y composer.lock para cachear esta capa
COPY composer.json composer.lock ./

# 4) Ejecuta composer install sin scripts (evita llamar artisan antes de copiar el código)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 5) Ahora copia el resto del proyecto (incluye artisan, rutas, etc.)
COPY . .

# 6) Corre los scripts que antes no pudimos ejecutar
RUN composer run-script post-autoload-dump

# 7) Ajusta permisos (según tu setup)
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
