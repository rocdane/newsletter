#!/bin/bash
set -e

# Configuration
SSH_HOST="${SSH_HOST:-votre-serveur.com}"
SSH_USER="${SSH_USER:-utilisateur}"
SSH_PORT="${SSH_PORT:-22}"
APP_DIR="/var/www/laravel-app"
REMOTE_BACKUP_DIR="/var/www/backups"

# Demander les credentials si non définis
if [ -z "$SSH_PASSWORD" ] && [ -z "$SSH_KEY_PATH" ]; then
    echo -n "Méthode d'authentification (key/password) [key]: "
    read AUTH_METHOD
    AUTH_METHOD=${AUTH_METHOD:-key}
    
    if [ "$AUTH_METHOD" = "password" ]; then
        echo -n "Mot de passe SSH: "
        read -s SSH_PASSWORD
        echo
    else
        echo -n "Chemin de la clé SSH [~/.ssh/id_rsa]: "
        read SSH_KEY_PATH
        SSH_KEY_PATH=${SSH_KEY_PATH:-~/.ssh/id_rsa}
    fi
fi

# Couleurs pour le logging
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# Fonction pour exécuter des commandes SSH
run_ssh() {
    local command="$1"
    if [ -n "$SSH_PASSWORD" ]; then
        sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p $SSH_PORT $SSH_USER@$SSH_HOST "$command"
    else
        ssh -o StrictHostKeyChecking=no -p $SSH_PORT -i "${SSH_KEY_PATH/#\~/$HOME}" $SSH_USER@$SSH_HOST "$command"
    fi
}

# Fonction pour transférer des fichiers
transfer_file() {
    local source="$1"
    local target="$2"
    if [ -n "$SSH_PASSWORD" ]; then
        sshpass -p "$SSH_PASSWORD" scp -o StrictHostKeyChecking=no -P $SSH_PORT "$source" $SSH_USER@$SSH_HOST:"$target"
    else
        scp -o StrictHostKeyChecking=no -P $SSH_PORT -i "${SSH_KEY_PATH/#\~/$HOME}" "$source" $SSH_USER@$SSH_HOST:"$target"
    fi
}

check_requirements() {
    log "Vérification des prérequis..."
    command -v ssh >/dev/null 2>&1 || error "SSH n'est pas installé"
    command -v tar >/dev/null 2>&1 || error "tar n'est pas installé"
    command -v composer >/dev/null 2>&1 || error "Composer n'est pas installé"
    
    if [ -n "$SSH_PASSWORD" ]; then
        command -v sshpass >/dev/null 2>&1 || error "sshpass n'est pas installé (sudo apt install sshpass)"
    fi
}

prepare_local() {
    log "Préparation locale..."
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
    [ -f package.json ] && npm run build 2>/dev/null || warn "Build npm ignoré"
    php artisan optimize:clear --no-interaction
}

deploy() {
    log "Déploiement sur le serveur..."
    
    # Créer une archive
    tar --exclude='.git' \
        --exclude='node_modules' \
        --exclude='storage' \
        --exclude='.env' \
        --exclude='deploy.sh' \
        -czf /tmp/deploy.tar.gz .
    
    # Transfert de l'archive
    transfer_file "/tmp/deploy.tar.gz" "/tmp/deploy.tar.gz"
    
    # Exécution du script de déploiement distant
    if [ -n "$SSH_PASSWORD" ]; then
        sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p $SSH_PORT $SSH_USER@$SSH_HOST 'bash -s' < remote-deploy.sh
    else
        ssh -o StrictHostKeyChecking=no -p $SSH_PORT -i "${SSH_KEY_PATH/#\~/$HOME}" $SSH_USER@$SSH_HOST 'bash -s' < remote-deploy.sh
    fi
    
    # Nettoyage local
    rm -f /tmp/deploy.tar.gz
}

main() {
    check_requirements
    prepare_local
    deploy
    log "Déploiement terminé avec succès !"
}

main "$@"