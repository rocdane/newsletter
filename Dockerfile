# syntax=docker/dockerfile:1.4
FROM php:8.3-fpm-alpine AS base

# Métadonnées
LABEL org.opencontainers.image.title="Laravel Mailing App"
LABEL org.opencontainers.image.description="Laravel application for email campaigns"
LABEL org.opencontainers.image.vendor="NumeaTech"

# Variables d'environnement
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Installation des dépendances système
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && rm -rf /var/cache/apk/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    gd \
    zip \
    intl \
    mbstring \
    pcntl \
    bcmath \
    sockets

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur non-root
RUN addgroup -g 1000 -S laravel \
    && adduser -u 1000 -S laravel -G laravel

# ================================
# Stage de développement
# ================================
FROM base AS development

# Installation des outils de développement
RUN apk add --no-cache \
    nodejs \
    npm

# Xdebug pour le développement
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuration Xdebug
RUN echo "xdebug.mode=develop,coverage,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# ================================
# Stage de test
# ================================
FROM development AS test

WORKDIR /var/www/html

# Copie des fichiers de configuration
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Installation des dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci --only=production

# Copie du code source
COPY . .

# Permissions
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Tests
RUN php artisan test --parallel

# ================================
# Stage de production
# ================================
FROM base AS production

WORKDIR /var/www/html

# Copie des fichiers de configuration
COPY composer.json composer.lock ./

# Installation des dépendances de production
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-cache

# Copie du code source
COPY . .

# Build des assets (si nécessaire)
# RUN npm ci --only=production && npm run build

# Configuration optimisée
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Permissions
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configuration Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configuration Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configuration PHP-FPM
RUN echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 4" >> /usr/local/etc/php-fpm.d/www.conf

# Exposition du port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Script d'entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]