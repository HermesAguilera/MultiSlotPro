## Etapa final: PHP
FROM php:8.2-cli
WORKDIR /var/www

## Dependencias del sistema (no interactivas, paquetes compatibles)
ENV DEBIAN_FRONTEND=noninteractive

# Actualizar índices y comprobar disponibilidad de paquetes antes de instalar
RUN apt-get update && apt-get -y upgrade || true
RUN apt-cache policy libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libpq-dev zlib1g-dev || true

# Instalar herramientas básicas primero (ayuda a aislar errores)
RUN apt-get install -y --no-install-recommends \
        ca-certificates gnupg curl \
    git unzip zip netcat-openbsd procps \
    && rm -rf /var/lib/apt/lists/*

# Instalar librerías de desarrollo por separado
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential libzip-dev zlib1g-dev \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libicu-dev libpq-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql bcmath zip intl gd mbstring \
    && apt-get purge -y --auto-remove build-essential \
    && rm -rf /var/lib/apt/lists/* /var/cache/apt/*

# Stable upload settings for Livewire/FilePond in containerized deployments.
RUN mkdir -p /tmp && chmod 1777 /tmp
RUN printf "upload_max_filesize=10M\npost_max_size=12M\nmax_file_uploads=20\nupload_tmp_dir=/tmp\nmemory_limit=256M\n" > /usr/local/etc/php/conf.d/uploads.ini

ENV DEBIAN_FRONTEND=dialog

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar código de la aplicación
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Publicar assets de Filament (no necesita la DB)
RUN php artisan filament:assets --no-interaction || true

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache public && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force && \
    php artisan app:ensure-plataformas-imagen-column && \
    mkdir -p storage/app/private/livewire-tmp storage/app/public/plataformas && \
    chmod -R 777 storage bootstrap/cache && \
    php artisan storage:link || true && \
    ([ "${RUN_DB_SEED_ON_BOOT:-false}" = "true" ] && php artisan db:seed --force || true) && \
    php artisan optimize:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000}