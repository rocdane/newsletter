# =============================================================================
# docker/entrypoint.sh
# =============================================================================
#!/bin/sh
set -e

echo "🚀 Starting Laravel Emailing Application..."

# Fonction d'attente pour les services
wait_for_service() {
    local host=$1
    local port=$2
    local service=$3
    
    echo "⏳ Waiting for $service ($host:$port)..."
    while ! nc -z $host $port 2>/dev/null; do
        sleep 1
    done
    echo "✅ $service is ready!"
}

# Attendre les services dépendants
if [ ! -z "$DB_HOST" ]; then
    wait_for_service $DB_HOST ${DB_PORT:-3306} "Database"
fi

if [ ! -z "$REDIS_HOST" ]; then
    wait_for_service $REDIS_HOST ${REDIS_PORT:-6379} "Redis"
fi

# Configuration de l'environnement
if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
    echo "🔧 Production environment detected"
    
    # Vérification des variables obligatoires
    if [ -z "$APP_KEY" ]; then
        echo "❌ APP_KEY is required in production!"
        exit 1
    fi
    
    if [ -z "$DB_PASSWORD" ]; then
        echo "❌ DB_PASSWORD is required in production!"
        exit 1
    fi
    
    # Migrations automatiques si activées
    if [ "$AUTO_MIGRATE" = "true" ]; then
        echo "🔄 Running database migrations..."
        php artisan migrate --force --no-interaction
    fi
    
    # Seeding si activé (attention en production!)
    if [ "$AUTO_SEED" = "true" ]; then
        echo "🌱 Running database seeding..."
        php artisan db:seed --force --no-interaction
    fi
    
else
    echo "🔧 Development environment detected"
    
    # Génération automatique de la clé en développement
    if [ -z "$APP_KEY" ]; then
        echo "🔑 Generating application key..."
        php artisan key:generate --no-interaction
    fi
    
    # Migrations et seeding automatiques en développement
    echo "🔄 Running migrations and seeding..."
    php artisan migrate:fresh --seed --no-interaction || true
fi

# Optimisations Laravel
echo "⚡ Optimizing Laravel application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Création du lien symbolique pour le storage
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link --no-interaction
fi

# Lancement du scheduler en arrière-plan
if [ "$ENABLE_SCHEDULER" = "true" ]; then
    echo "⏰ Starting Laravel scheduler..."
    (while [ true ]; do
        php artisan schedule:run --verbose --no-interaction
        sleep 60
    done) &
fi

# Lancement des workers de queue
if [ "$ENABLE_QUEUE" = "true" ]; then
    echo "📨 Queue workers will be managed by Supervisor"
fi

echo "✅ Laravel application is ready!"

# Exécution de la commande passée en paramètre
exec "$@"
