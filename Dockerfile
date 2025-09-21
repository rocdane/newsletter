# -------------------------------
# Dockerfile Laravel 12 - PHP 8.3 + Node.js + Redis
# -------------------------------

# 1. Image de base PHP avec extensions nécessaires
FROM php:8.3-fpm

# 2. Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Installer les dépendances système
RUN apt-get update && apt-get install -y git curl zip unzip libzip-dev libonig-dev libpng-dev libxml2-dev libicu-dev libpq-dev libjpeg-dev libfreetype6-dev libwebp-dev libsodium-dev nodejs npm pkg-config
RUN rm -rf /var/lib/apt/lists/*

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

# 4 Création de l'utilisateur non-root
RUN addgroup -g 1000 -S laravel && adduser -u 1000 -S laravel -G laravel

RUN chown -R laravel:laravel /var/www/html

# 5. Définir le répertoire de travail
WORKDIR /var/www/html

# 6. Copier les fichiers du projet
COPY . .

# 7. Installer les dépendances PHP et Node.js
RUN composer install --prefer-dist --no-interaction --optimize-autoloader --no-dev --verbose
RUN npm ci && npm run build

# 8. Copier l’exemple d’environnement
RUN cp .env.prod .env && php artisan key:generate

# 9. Permissions des dossiers storage et bootstrap
RUN mkdir -p storage/framework/{sessions,views,cache} && chmod -R 755 storage bootstrap/cache

# Configuration Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf

# Configuration Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configuration PHP-FPM
RUN echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 4" >> /usr/local/etc/php-fpm.d/www.conf

# 10. Exposer le port PHP-FPM
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Script d'entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
