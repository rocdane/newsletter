#!/bin/bash

set -e

# =============================================================================
# Script de déploiement pour l'application Laravel (Environnement Plesk)
# =============================================================================

# Variables par défaut (à adapter selon votre configuration Plesk)
APP_NAME="${APP_NAME:-Promosletter}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/vhosts/promosletter.com/httpdocs}"
ENVIRONMENT="${ENVIRONMENT:-production}"
APP_URL="${APP_URL:-}"

# Variables de déploiement
export APP_DIR="$DEPLOY_PATH"
export BACKUP_DIR="/var/www/vhosts/promosletter.com/backups"
export TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Couleurs pour les logs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }
info() { echo -e "${BLUE}[STEP]${NC} $1"; }

# Détection automatique des utilisateurs Plesk
PLESK_USER=$(stat -c '%U' "$DEPLOY_PATH" 2>/dev/null || echo "")
PLESK_GROUP=$(stat -c '%G' "$DEPLOY_PATH" 2>/dev/null || echo "")

log "🚀 Starting deployment of $APP_NAME on Plesk environment..."
log "📍 Deploy path: $APP_DIR"
log "👤 Plesk user: ${PLESK_USER:-auto-detect}"
log "👥 Plesk group: ${PLESK_GROUP:-auto-detect}"

# Vérification des prérequis
info "🔍 Checking prerequisites..."
if [ ! -d "$APP_DIR" ]; then
    error "❌ Application directory does not exist: $APP_DIR"
    exit 1
fi

if [ ! -f "/tmp/deployment.tar.gz" ]; then
    error "❌ Deployment archive not found at /tmp/deployment.tar.gz"
    echo "Please upload the deployment archive first using:"
    echo "scp deployment.tar.gz user@server:/tmp/"
    exit 1
fi

# =============================================================================
# Sauvegarde AVANT de toucher aux fichiers
# =============================================================================
info "💾 Creating backup..."
mkdir -p "$BACKUP_DIR"
if [ -d "$APP_DIR" ]; then
    cp -r "$APP_DIR" "$BACKUP_DIR/backup_$TIMESTAMP"
    log "✅ Backup saved to: $BACKUP_DIR/backup_$TIMESTAMP"
else
    warn "⚠️ No existing application to backup"
fi

# =============================================================================
# Arrêt des processus (si configurés)
# =============================================================================
info "⏸️ Stopping application processes..."
if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl stop $APP_NAME-worker:* 2>/dev/null || warn "No workers to stop"
    supervisorctl stop $APP_NAME-reverb 2>/dev/null || warn "No reverb to stop"
fi

# Arrêt du scheduler Laravel (cron)
log "Temporarily disabling Laravel scheduler..."

# =============================================================================
# Extraction et remplacement des fichiers
# =============================================================================
info "📦 Extracting new version..."

# Sauvegarder les fichiers critiques
if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "/tmp/.env.backup"
    log "✅ Environment file backed up"
fi

if [ -d "$APP_DIR/storage" ]; then
    cp -r "$APP_DIR/storage" "/tmp/storage.backup"
    log "✅ Storage directory backed up"
fi

# Extraire vers un dossier temporaire puis synchroniser
tar -xzf /tmp/deployment.tar.gz -C /tmp/
rsync -av --delete \
    --exclude='.env' \
    --exclude='storage/app' \
    --exclude='storage/logs' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/cache' \
    /tmp/deployment/ "$APP_DIR/"

rm -rf /tmp/deployment*
log "✅ Files synchronized"

# =============================================================================
# Restaurer les fichiers persistants
# =============================================================================
info "📋 Restoring persistent files..."

# Restaurer .env
if [ -f "/tmp/.env.backup" ]; then
    cp "/tmp/.env.backup" "$APP_DIR/.env"
    rm "/tmp/.env.backup"
    log "✅ Environment file restored"
else
    if [ -f "$APP_DIR/.env.example" ]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        warn "⚠️ Using .env.example as template - please configure manually"
    else
        error "❌ No environment file found"
        exit 1
    fi
fi

# Restaurer storage
if [ -d "/tmp/storage.backup" ]; then
    mkdir -p "$APP_DIR/storage"
    cp -r /tmp/storage.backup/* "$APP_DIR/storage/" 2>/dev/null || true
    rm -rf /tmp/storage.backup
    log "✅ Storage files restored"
fi

# =============================================================================
# Configuration des permissions (Plesk)
# =============================================================================
info "🔧 Setting up Plesk permissions..."

# Créer les répertoires manquants avec les bonnes permissions
mkdir -p "$APP_DIR/storage/framework/cache"
mkdir -p "$APP_DIR/storage/framework/sessions"
mkdir -p "$APP_DIR/storage/framework/views"
mkdir -p "$APP_DIR/storage/logs"
mkdir -p "$APP_DIR/storage/app/public"
mkdir -p "$APP_DIR/bootstrap/cache"

# Permissions pour environnement Plesk
if [ -n "$PLESK_USER" ] && [ -n "$PLESK_GROUP" ]; then
    chown -R "$PLESK_USER:$PLESK_GROUP" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    log "✅ Set ownership to $PLESK_USER:$PLESK_GROUP"
else
    # Fallback pour permissions génériques
    chmod -R 755 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    warn "⚠️ Applied generic permissions (could not detect Plesk user)"
fi

# Permissions spécifiques pour les dossiers sensibles
chmod -R 755 "$APP_DIR/storage"
chmod -R 755 "$APP_DIR/bootstrap/cache"

log "✅ Permissions configured for Plesk environment"

# =============================================================================
# Installation des dépendances (si nécessaire)
# =============================================================================
info "📦 Checking dependencies..."
cd "$APP_DIR"

if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    log "Installing Composer dependencies..."
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
        log "✅ Composer dependencies installed"
    else
        error "❌ Composer not found - please install dependencies manually"
    fi
fi

# =============================================================================
# Configuration Laravel
# =============================================================================
info "⚡ Configuring Laravel application..."

# Variables d'environnement pour PHP CLI (si différent de web)
export PHP_CLI_PATH=$(which php)
log "Using PHP CLI: $PHP_CLI_PATH"

# Nettoyage des caches
$PHP_CLI_PATH artisan config:clear --no-interaction 2>/dev/null || warn "Could not clear config cache"
$PHP_CLI_PATH artisan route:clear --no-interaction 2>/dev/null || warn "Could not clear route cache"
$PHP_CLI_PATH artisan view:clear --no-interaction 2>/dev/null || warn "Could not clear view cache"
$PHP_CLI_PATH artisan cache:clear --no-interaction 2>/dev/null || warn "Could not clear application cache"

# Génération de la clé si nécessaire
if ! grep -q "APP_KEY=base64:" .env; then
    $PHP_CLI_PATH artisan key:generate --no-interaction
    log "✅ Application key generated"
fi

# Migrations (uniquement en production avec confirmation)
if [ "$ENVIRONMENT" = "production" ]; then
    echo ""
    warn "⚠️ PRODUCTION ENVIRONMENT DETECTED"
    read -p "Do you want to run database migrations? (yes/no): " MIGRATE_CONFIRM
    if [ "$MIGRATE_CONFIRM" = "yes" ]; then
        log "🗃️ Running database migrations..."
        $PHP_CLI_PATH artisan migrate --force --no-interaction
        log "✅ Migrations completed"
    else
        warn "⚠️ Migrations skipped - run manually if needed"
    fi
else
    log "🗃️ Running database migrations (staging)..."
    $PHP_CLI_PATH artisan migrate --force --no-interaction
fi

# Optimisations de production
info "🚀 Applying optimizations..."
$PHP_CLI_PATH artisan config:cache --no-interaction
$PHP_CLI_PATH artisan route:cache --no-interaction
$PHP_CLI_PATH artisan view:cache --no-interaction
$PHP_CLI_PATH artisan storage:link --no-interaction 2>/dev/null || warn "Storage link already exists or failed"

log "✅ Laravel optimizations applied"

# =============================================================================
# Configuration des services (si nécessaire)
# =============================================================================
info "🔨 Configuring services..."

# Pour Plesk, les services sont généralement gérés différemment
# Vérifier si Supervisor est disponible
if command -v supervisorctl >/dev/null 2>&1; then
    log "Supervisor detected - configuring Laravel services..."
    
    # Configuration worker
    cat > "/etc/supervisor/conf.d/$APP_NAME-worker.conf" << SUPERVISOR_CONF
[program:$APP_NAME-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_CLI_PATH $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=512
directory=$APP_DIR
user=${PLESK_USER:-www-data}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopwaitsecs=3600
SUPERVISOR_CONF

    # Configuration Reverb (si utilisé)
    if grep -q "REVERB_" .env; then
        cat > "/etc/supervisor/conf.d/$APP_NAME-reverb.conf" << REVERB_CONF
[program:$APP_NAME-reverb]
process_name=%(program_name)s
command=$PHP_CLI_PATH $APP_DIR/artisan reverb:start --host=0.0.0.0 --port=8080
directory=$APP_DIR
user=${PLESK_USER:-www-data}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/reverb.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
REVERB_CONF
    fi
    
    # Redémarrer Supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start $APP_NAME-worker:*
    supervisorctl start $APP_NAME-reverb 2>/dev/null || true
    
    log "✅ Supervisor services configured"
else
    warn "⚠️ Supervisor not available - configure queue workers manually"
    echo "To run queue workers manually:"
    echo "$PHP_CLI_PATH $APP_DIR/artisan queue:work --daemon"
fi

# =============================================================================
# Configuration du scheduler (cron)
# =============================================================================
info "⏰ Setting up Laravel scheduler..."
CRON_COMMAND="* * * * * cd $APP_DIR && $PHP_CLI_PATH artisan schedule:run >> /dev/null 2>&1"

# Vérifier si le cron existe déjà
if ! crontab -l 2>/dev/null | grep -q "$APP_DIR.*schedule:run"; then
    (crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -
    log "✅ Laravel scheduler added to crontab"
else
    log "✅ Laravel scheduler already configured"
fi

# =============================================================================
# Nettoyage et validation
# =============================================================================
info "🧹 Cleaning up..."

# Nettoyer les anciens backups (garder les 5 derniers)
if [ -d "$BACKUP_DIR" ]; then
    ls -dt "$BACKUP_DIR/backup_"* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true
    log "✅ Old backups cleaned"
fi

# Test de l'application
info "🧪 Testing deployment..."
if $PHP_CLI_PATH artisan --version >/dev/null 2>&1; then
    APP_VERSION=$($PHP_CLI_PATH artisan --version)
    log "✅ Laravel application is working: $APP_VERSION"
else
    error "❌ Laravel application test failed"
    echo ""
    echo "🔧 Manual troubleshooting steps:"
    echo "1. Check file permissions in $APP_DIR/storage"
    echo "2. Verify .env configuration"
    echo "3. Check PHP error logs"
    echo "4. Verify database connection"
    exit 1
fi

# Test des permissions d'écriture
if [ -w "$APP_DIR/storage/logs" ]; then
    echo "Test write" > "$APP_DIR/storage/logs/deploy-test.log" && rm "$APP_DIR/storage/logs/deploy-test.log"
    log "✅ Storage directory is writable"
else
    warn "⚠️ Storage directory might not be writable"
fi

# =============================================================================
# Rapport final
# =============================================================================
echo ""
log "🎉 Deployment completed successfully!"
echo ""
info "📊 Deployment Summary:"
echo "• Application: $APP_NAME"
echo "• Environment: $ENVIRONMENT"
echo "• Path: $APP_DIR"
echo "• PHP Version: $($PHP_CLI_PATH --version | head -1)"
echo "• Laravel Version: $APP_VERSION"
echo "• Backup: $BACKUP_DIR/backup_$TIMESTAMP"
echo ""

if [ -n "$APP_URL" ]; then
    log "🌍 Application URL: $APP_URL"
else
    log "🌍 Configure your domain to point to: $APP_DIR/public"
fi

echo ""
info "📝 Next steps:"
echo "1. Test the application in your browser"
echo "2. Verify queue workers are running (if using queues)"
echo "3. Check application logs for any issues"
echo "4. Update any environment-specific configurations"

if [ "$ENVIRONMENT" = "production" ]; then
    echo ""
    warn "🔒 Production environment reminders:"
    echo "• Ensure SSL certificate is configured"
    echo "• Verify backup procedures are in place"
    echo "• Monitor application performance and logs"
    echo "• Keep dependencies updated regularly"
fi