# -------------------------------
# Dockerfile Laravel 12 - PHP 8.3 + Node.js + Redis
# -------------------------------

# 1. Image de base PHP avec extensions nécessaires
FROM php:8.3-fpm

# 2. Installer les dépendances système
RUN apt-get update && apt-get install -y git curl zip unzip libzip-dev libonig-dev libpng-dev libxml2-dev libicu-dev libpq-dev libjpeg-dev libfreetype6-dev libwebp-dev libsodium-dev nodejs npm pkg-config
RUN rm -rf /var/lib/apt/lists/*

# Your existing Dockerfile content above...

# Configure GD extension first
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp

# Configure intl extension
RUN docker-php-ext-configure intl

# Install PHP extensions (split into logical groups for better caching)
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath xml zip intl opcache sockets

# Install GD extension separately after configuration
RUN docker-php-ext-install gd

# Install sodium extension separately
RUN docker-php-ext-install sodium

RUN pecl install redis && docker-php-ext-enable redis

# 4. Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Définir le répertoire de travail
WORKDIR /var/www/html

# 6. Copier les fichiers du projet
COPY . .

# 7. Installer les dépendances PHP et Node.js
RUN composer install --prefer-dist --no-interaction --optimize-autoloader --no-dev --verbose
RUN npm ci && npm run build

# 8. Copier l’exemple d’environnement
RUN cp .env.example .env && php artisan key:generate

# 9. Permissions des dossiers storage et bootstrap
RUN mkdir -p storage/framework/{sessions,views,cache} && chmod -R 777 storage bootstrap/cache

# 10. Exposer le port PHP-FPM
EXPOSE 9000

# 11. Commande par défaut pour PHP-FPM
CMD ["php-fpm"]
