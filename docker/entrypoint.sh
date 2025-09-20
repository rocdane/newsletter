#!/bin/sh
set -e

echo "🚀 Starting Laravel Mailing Application..."

# Attendre que la base de données soit prête
if [ ! -z "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    while ! nc -z $DB_HOST ${DB_PORT:-3306}; do
        sleep 1
    done
    echo "✅ Database connection established!"
fi

# Migrations automatiques en production
if [ "$APP_ENV" = "production" ] && [ "$AUTO_MIGRATE" = "true" ]; then
    echo "🔄 Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Génération de la clé d'application si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --no-interaction
fi

# Nettoyage du cache
echo "🧹 Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimisation pour la production
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Lancement du scheduler en arrière-plan
echo "⏰ Starting Laravel scheduler..."
(while [ true ]; do
    php artisan schedule:run --verbose --no-interaction &
    sleep 60
done) &

# Lancement de la queue worker
echo "📨 Starting queue workers..."
php artisan queue:restart

echo "✅ Laravel application ready!"

# Exécution de la commande passée en paramètre
exec "$@"