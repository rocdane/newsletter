#!/bin/bash
set -e

# Variables
APP_DIR="/var/www/laravel-app"
BACKUP_DIR="/var/www/backups"
TIMESTAMP=$(date +%Y%m%d%H%M%S)
NEW_DIR="$APP_DIR-$TIMESTAMP"

# Couleurs pour le logging
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Fonction pour exécuter des commandes avec sudo si nécessaire
run_cmd() {
    if [ "$EUID" -eq 0 ]; then
        $@
    else
        sudo $@
    fi
}

# Déploiement principal
main() {
    log "Début du déploiement..."
    
    # Créer les répertoires si nécessaire
    run_cmd mkdir -p "$APP_DIR" "$BACKUP_DIR" "$NEW_DIR"
    run_cmd chown -R $(whoami):www-data "$NEW_DIR"
    run_cmd chmod -R 775 "$NEW_DIR"
    
    # Extraire l'archive
    log "Extraction de l'archive..."
    tar -xzf /tmp/deployment.tar.gz -C /tmp/
    run_cmd mv /tmp/deployment/* "$NEW_DIR/"
    run_cmd rm -rf /tmp/deployment
    
    # Backup de l'ancienne version
    if [ -d "$APP_DIR" ]; then
        log "Sauvegarde de l'ancienne version..."
        run_cmd cp -r "$APP_DIR" "$BACKUP_DIR/backup-$TIMESTAMP"
    fi
    
    # Copier les fichiers de configuration
    if [ -d "$APP_DIR" ]; then
        if [ -f "$APP_DIR/.env" ]; then
            cp "$APP_DIR/.env" "$NEW_DIR/.env"
            log "Fichier .env copié"
        fi
        
        if [ -d "$APP_DIR/storage" ]; then
            cp -r "$APP_DIR/storage" "$NEW_DIR/"
            log "Dossier storage copié"
        fi
    else
        warn "Premier déploiement - configuration manuelle nécessaire"
    fi
    
    # Mise à jour du symlink
    log "Mise à jour du symlink..."
    run_cmd rm -f "$APP_DIR"
    run_cmd ln -sf "$NEW_DIR" "$APP_DIR"
    
    # Aller dans le répertoire de l'application
    cd "$APP_DIR"
    
    # Installation des dépendances
    log "Installation des dépendances Composer..."
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
    
    # Migrations et optimisations
    log "Exécution des migrations..."
    php artisan migrate --force --no-interaction
    
    log "Optimisation de l'application..."
    php artisan optimize:clear --no-interaction
    php artisan optimize --no-interaction
    
    if [ -f artisan ]; then
        php artisan storage:link --no-interaction
    fi
    
    # Mise à jour des permissions
    log "Mise à jour des permissions..."
    run_cmd chown -R www-data:www-data storage bootstrap/cache
    run_cmd chmod -R 775 storage bootstrap/cache
    
    # Redémarrage des services
    log "Redémarrage des services..."
    if command -v systemctl >/dev/null 2>&1; then
        run_cmd systemctl reload php8.2-fpm 2>/dev/null || true
        run_cmd systemctl reload nginx 2>/dev/null || true
    else
        warn "Systemctl non disponible - redémarrage manuel nécessaire"
    fi
    
    # Nettoyage des anciens déploiements
    log "Nettoyage des anciens déploiements..."
    if [ -d "$BACKUP_DIR" ]; then
        # Garder les 5 derniers backups
        ls -dt "$BACKUP_DIR"/backup-* 2>/dev/null | tail -n +6 | xargs run_cmd rm -rf || true
        # Garder les 5 derniers déploiements
        ls -dt "$APP_DIR"-* 2>/dev/null | tail -n +6 | xargs run_cmd rm -rf || true
    fi
    
    # Nettoyage final
    run_cmd rm -f /tmp/deployment.tar.gz
    
    log "✅ Déploiement terminé avec succès !"
    log "Nouvelle version: $NEW_DIR"
}

# Gestion des erreurs
trap 'error "Erreur lors du déploiement à la ligne $LINENO"' ERR

main "$@"