#!/bin/bash

set -e

# =============================================================================
# Script de déploiement pour l'application Laravel
# =============================================================================

# Récupération des paramètres depuis les variables d'environnement
APP_NAME="${APP_NAME:-Promosletter}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/vhosts/promosletter.com/httpdocs}"
ENVIRONMENT="${ENVIRONMENT:-production}"
APP_URL="${APP_URL:-}"

# Variables de déploiement
export APP_DIR="$DEPLOY_PATH"
export BACKUP_DIR="/var/www/backups"
export TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Couleurs pour les logs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

log "🚀 Starting direct deployment of $APP_NAME..."
log "📍 Deploy path: $APP_DIR"

# =============================================================================
# Sauvegarde AVANT de toucher aux fichiers
# =============================================================================
if [ -d "$APP_DIR" ]; then
  log "💾 Creating backup of current version..."
  sudo mkdir -p "$BACKUP_DIR"
  sudo cp -r "$APP_DIR" "$BACKUP_DIR/backup_$TIMESTAMP"
  log "✅ Backup saved to: $BACKUP_DIR/backup_$TIMESTAMP"
fi

# =============================================================================
# Arrêt temporaire des services pour éviter les conflits
# =============================================================================
log "⏸️ Temporarily stopping services..."
if command -v supervisorctl >/dev/null 2>&1; then
  sudo supervisorctl stop $APP_NAME-worker:* 2>/dev/null || true
  sudo supervisorctl stop $APP_NAME-reverb 2>/dev/null || true
fi

# =============================================================================
# Extraction et remplacement des fichiers
# =============================================================================
log "📦 Extracting new version..."
if [ ! -f "/tmp/deployment.tar.gz" ]; then
  error "❌ Deployment archive not found!"
  exit 1
fi

# Créer le répertoire s'il n'existe pas
sudo mkdir -p "$APP_DIR"

# Sauvegarder les fichiers critiques AVANT l'extraction
if [ -f "$APP_DIR/.env" ]; then
  cp "$APP_DIR/.env" "/tmp/.env.backup"
  log "✅ Environment file backed up"
fi

if [ -d "$APP_DIR/storage" ]; then
  cp -r "$APP_DIR/storage" "/tmp/storage.backup"
  log "✅ Storage directory backed up"
fi

# Extraire la nouvelle version DIRECTEMENT dans APP_DIR
tar -xzf /tmp/deployment.tar.gz -C /tmp/
sudo rsync -av --delete \
  --exclude='.env' \
  --exclude='storage/app' \
  --exclude='storage/logs' \
  /tmp/deployment/ "$APP_DIR/"

rm -rf /tmp/deployment*

# =============================================================================
# Restaurer les fichiers persistants
# =============================================================================
log "📋 Restoring persistent files..."

# Restaurer .env
if [ -f "/tmp/.env.backup" ]; then
  cp "/tmp/.env.backup" "$APP_DIR/.env"
  rm "/tmp/.env.backup"
  log "✅ Environment file restored"
else
  cp "$APP_DIR/.env.prod" "$APP_DIR/.env"
  cd "$APP_DIR"
  php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env
  warn "⚠️ New .env created with generated APP_KEY"
fi

# Restaurer storage
if [ -d "/tmp/storage.backup" ]; then
  # Fusionner les dossiers storage
  sudo cp -r /tmp/storage.backup/app "$APP_DIR/storage/" 2>/dev/null || true
  sudo cp -r /tmp/storage.backup/logs "$APP_DIR/storage/" 2>/dev/null || true
  sudo rm -rf /tmp/storage.backup
  log "✅ Storage files restored"
fi

# =============================================================================
# Configuration de l'application (DANS APP_DIR)
# =============================================================================
cd "$APP_DIR"
log "🔧 Configuring Laravel application..."

# Créer les répertoires manquants
sudo mkdir -p storage/framework/{cache,sessions,views}
sudo mkdir -p storage/logs
sudo mkdir -p storage/app/public
sudo mkdir -p bootstrap/cache

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Optimisations Laravel
log "⚡ Running Laravel optimizations..."
sudo -u www-data php artisan config:clear --no-interaction
sudo -u www-data php artisan route:clear --no-interaction
sudo -u www-data php artisan view:clear --no-interaction
sudo -u www-data php artisan cache:clear --no-interaction

# Migrations
if [ "$ENVIRONMENT" = "production" ]; then
  log "🗃️ Running database migrations..."
  sudo -u www-data php artisan migrate:fresh --force --no-interaction
fi

# Optimisations de production
log "🚀 Applying production optimizations..."
sudo -u www-data php artisan config:cache --no-interaction
sudo -u www-data php artisan route:cache --no-interaction
sudo -u www-data php artisan view:cache --no-interaction
sudo -u www-data php artisan event:cache --no-interaction
sudo -u www-data php artisan storage:link --no-interaction || true

# =============================================================================
# Redémarrage des services web
# =============================================================================
log "🔄 Reloading web services..."
if sudo systemctl is-active --quiet php8.3-fpm; then
  sudo systemctl reload php8.3-fpm
elif sudo systemctl is-active --quiet php8.2-fpm; then
  sudo systemctl reload php8.2-fpm
fi

if sudo systemctl is-active --quiet nginx; then
  sudo nginx -t && sudo systemctl reload nginx
fi

# =============================================================================
# Configuration et redémarrage des services Laravel
# =============================================================================
log "🔨 Configuring and restarting Laravel services..."

# Configuration Supervisor (avec les bons chemins)
sudo tee /etc/supervisor/conf.d/$APP_NAME-worker.conf > /dev/null << SUPERVISOR_CONF
[program:$APP_NAME-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=512
directory=$APP_DIR
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3
stopwaitsecs=3600
SUPERVISOR_CONF

sudo tee /etc/supervisor/conf.d/$APP_NAME-reverb.conf > /dev/null << REVERB_CONF
[program:$APP_NAME-reverb]
process_name=%(program_name)s
command=php $APP_DIR/artisan reverb:start --host=0.0.0.0 --port=8080
directory=$APP_DIR
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/reverb.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3
stderr_logfile=$APP_DIR/storage/logs/reverb_error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=3
REVERB_CONF

# Redémarrer Supervisor
if command -v supervisorctl >/dev/null 2>&1; then
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start $APP_NAME-worker:*
  sudo supervisorctl start $APP_NAME-reverb
  
  sleep 3
  log "📊 Services status:"
  sudo supervisorctl status $APP_NAME-worker:* $APP_NAME-reverb
fi

# Redémarrer les services Laravel
sudo -u www-data php artisan queue:restart --no-interaction || true
sudo -u www-data php artisan reverb:restart --no-interaction || true

# =============================================================================
# Nettoyage et tests
# =============================================================================
log "🧹 Cleaning up old backups..."
if [ -d "$BACKUP_DIR" ]; then
  ls -dt "$BACKUP_DIR/backup_"* 2>/dev/null | tail -n +6 | xargs sudo rm -rf 2>/dev/null || true
fi

log "🧪 Testing deployment..."
if sudo -u www-data php artisan --version >/dev/null 2>&1; then
  log "✅ Laravel application is working"
else
  error "❌ Laravel application test failed"
  exit 1
fi

# =============================================================================
# Rapport final
# =============================================================================
log "🎉 Direct deployment completed successfully!"
log "📍 Application path: $APP_DIR"
log "🌍 Application available at: ${APP_URL:-your-domain.com}"
log "💾 Backup saved to: $BACKUP_DIR/backup_$TIMESTAMP"