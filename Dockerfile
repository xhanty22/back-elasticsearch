FROM php:8.2-fpm-buster

# Instalar dependencias necesarias en un solo paso
RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
    build-essential \
    libmcrypt-dev \
    nano \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    screen \
    gnupg2 \
    libjpeg62-turbo-dev \
    libwebp-dev \
    wget \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/9/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configurar directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto al contenedor
COPY . .

# Instalar Composer y Roadrunner desde contenedores específicos
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --from=spiralscout/roadrunner:2.4.2 /usr/bin/rr /usr/bin/rr

# Instalar dependencias del proyecto
RUN composer update --no-dev

# Ejecutar comandos de php artisan 
# RUN php artisan config:cache 
# RUN php artisan route:cache 
# RUN php artisan view:cache

# Comando por defecto
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=1532"]

# Exponer el puerto para la aplicación 
EXPOSE 1532