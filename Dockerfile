# syntax=docker/dockerfile:1.4
# =============================================================================
# Dockerfile Laravel 12 - Production Ready Multi-stage
# =============================================================================

# =============================================================================
# Base Stage - Dépendances système communes
# =============================================================================
FROM php:8.3-fpm-alpine AS base

# Métadonnées OCI
LABEL org.opencontainers.image.title="Laravel Emailing App"
LABEL org.opencontainers.image.description="Application Laravel pour campagnes d'emailing"
LABEL org.opencontainers.image.vendor="Your Organization"
LABEL org.opencontainers.image.licenses="MIT"

# Variables d'environnement globales
ENV PHP_MEMORY_LIMIT=256M
ENV PHP_MAX_EXECUTION_TIME=300
ENV PHP_UPLOAD_MAX_FILESIZE=20M
ENV PHP_POST_MAX_SIZE=20M
ENV APP_ENV=production
ENV APP_DEBUG=false

# Installation des dépendances système (optimisée)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS
RUN apk add --no-cache nginx supervisor curl zip unzip git nodejs npm libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev icu-dev oniguruma-dev libxml2-dev libsodium-dev bash redis
RUN rm -rf /var/cache/apk/*

# Configuration et installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg 

RUN docker-php-ext-configure intl 

RUN docker-php-ext-install pdo_mysql gd zip intl mbstring pcntl bcmath sockets xml opcache sodium

RUN pecl install redis

RUN docker-php-ext-enable redis

RUN apk del .build-deps

RUN docker-php-source delete

# Installation de Composer (version fixe pour reproductibilité)
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur non-root
RUN addgroup -g 1000 -S laravel
RUN adduser -u 1000 -S laravel -G laravel -s /bin/bash

# Configuration PHP optimisée
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo "memory_limit=${PHP_MEMORY_LIMIT}"; \
    echo "max_execution_time=${PHP_MAX_EXECUTION_TIME}"; \
    echo "upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE}"; \
    echo "post_max_size=${PHP_POST_MAX_SIZE}"; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# =============================================================================
# Dependencies Stage - Installation des dépendances
# =============================================================================
FROM base AS dependencies

USER laravel

# Copie des fichiers de dépendances
COPY --chown=laravel:laravel composer.json composer.lock ./
COPY --chown=laravel:laravel package.json package-lock.json ./

# Installation des dépendances PHP
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --optimize-autoloader

# Installation des dépendances Node.js
RUN npm ci --only=production --no-audit --no-fund

# =============================================================================
# Build Stage - Build des assets
# =============================================================================
FROM dependencies AS build

USER laravel

# Copie du code source
COPY --chown=laravel:laravel . .

# Finalisation de Composer
RUN composer dump-autoload --optimize --classmap-authoritative

# Build des assets
RUN npm run build

# Optimisations Laravel
RUN cp .env.example .env
RUN php artisan key:generate --no-interaction
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan event:cache

# =============================================================================
# Test Stage - Exécution des tests
# =============================================================================
FROM build AS test

USER laravel

# Installation des dépendances de développement
RUN composer install --dev --no-interaction

# Configuration pour les tests
RUN cp .env.example .env.testing
RUN php artisan key:generate --env=testing --no-interaction

# Exécution des tests
RUN php artisan test --env=testing --no-interaction --stop-on-failure

# =============================================================================
# Production Stage - Image finale
# =============================================================================
FROM base AS production

# Configuration Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf

# Configuration Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configuration PHP-FPM optimisée
RUN { \
    echo '[www]'; \
    echo 'user = laravel'; \
    echo 'group = laravel'; \
    echo 'listen = 127.0.0.1:9000'; \
    echo 'listen.owner = laravel'; \
    echo 'listen.group = laravel'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 20'; \
    echo 'pm.start_servers = 3'; \
    echo 'pm.min_spare_servers = 2'; \
    echo 'pm.max_spare_servers = 4'; \
    echo 'pm.max_requests = 1000'; \
    } > /usr/local/etc/php-fpm.d/www.conf

# Script d'entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copie des dépendances et du code depuis les stages précédents
COPY --from=dependencies --chown=laravel:laravel /var/www/html/vendor ./vendor
COPY --from=build --chown=laravel:laravel /var/www/html .

# Création des répertoires et permissions
RUN mkdir -p storage/framework/{cache,sessions,views}
RUN mkdir -p storage/logs
RUN mkdir -p bootstrap/cache
RUN chown -R laravel:laravel storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Exposition des ports
EXPOSE 80 9000 8080

# Configuration du volume pour les données persistantes
VOLUME ["/var/www/html/storage"]

USER laravel

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# =============================================================================
# Development Stage - Pour le développement local
# =============================================================================
FROM build AS development

USER root

# Installation de Xdebug et outils de développement
RUN apk add --no-cache $PHPIZE_DEPS
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN apk del $PHPIZE_DEPS

# Configuration Xdebug
RUN { \
    echo 'xdebug.mode=develop,coverage,debug'; \
    echo 'xdebug.start_with_request=yes'; \
    echo 'xdebug.client_host=host.docker.internal'; \
    echo 'xdebug.client_port=9003'; \
    echo 'xdebug.log=/tmp/xdebug.log'; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Installation des dépendances de développement
RUN composer install --dev --no-interaction

USER laravel

# Configuration pour le développement
ENV APP_ENV=local
ENV APP_DEBUG=true

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]