#!/bin/bash

# Configuration SSH
SSH_USER=${SSH_USER:-"your_username"}
SSH_HOST=${SSH_HOST:-"your-server.com"}
SSH_PORT= ${SSH_PORT:-22}
SSH_KEY=${SSH_KEY:-"$HOME/.ssh/id_rsa"}
SSH_PASSWORD=${SSH_PASSWORD:-""}

# Fonction pour détecter le terminal disponible
detect_terminal() {
    if command -v gnome-terminal &> /dev/null; then
        echo "gnome-terminal"
    elif command -v konsole &> /dev/null; then
        echo "konsole"
    elif command -v xterm &> /dev/null; then
        echo "xterm"
    elif command -v terminator &> /dev/null; then
        echo "terminator"
    else
        echo "unknown"
    fi
}

# Fonction de connexion SSH
connect_ssh() {
    local terminal=$(detect_terminal)
    local ssh_command="ssh -i $SSH_KEY -p $SSH_PORT $SSH_USER@$SSH_HOST"
    
    case $terminal in
        "gnome-terminal")
            gnome-terminal --title="SSH: $SSH_HOST" -- bash -c "$ssh_command; exec bash"
            ;;
        "konsole")
            konsole --title "SSH: $SSH_HOST" -e bash -c "$ssh_command; exec bash"
            ;;
        "xterm")
            xterm -title "SSH: $SSH_HOST" -e bash -c "$ssh_command; exec bash" &
            ;;
        "terminator")
            terminator --title="SSH: $SSH_HOST" -x bash -c "$ssh_command; exec bash"
            ;;
        *)
            echo "Aucun terminal graphique détecté. Connexion directe:"
            $ssh_command
            ;;
    esac
}

# Fonction pour validation des paramètres
validate_config() {
    if [ "$SSH_USER" = "your_username" ] || [ "$SSH_HOST" = "your-server.com" ]; then
        echo "❌ Erreur: Veuillez configurer SSH_USER et SSH_HOST dans le script"
        echo "SSH_USER: $SSH_USER"
        echo "SSH_HOST: $SSH_HOST"
        exit 1
    fi
    
    if [ ! -f "${SSH_KEY/#\~/$HOME}" ]; then
        echo "⚠️  Avertissement: Clé SSH non trouvée à $SSH_KEY"
        echo "Tentative de connexion avec authentification par mot de passe..."
    fi
}

# Fonction principale
main() {
    echo "🔗 Ouverture d'un nouveau terminal pour connexion SSH..."
    echo "Serveur: $SSH_USER@$SSH_HOST:$SSH_PORT"
    
    validate_config
    connect_ssh
    
    echo "✅ Commande de connexion SSH lancée dans un nouveau terminal"
}

# Point d'entrée avec gestion des arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [options]"
        echo "Options:"
        echo "  --help, -h     Afficher cette aide"
        echo "  --test         Tester la connexion sans ouvrir de terminal"
        echo ""
        echo "Configuration:"
        echo "  SSH_USER: $SSH_USER"
        echo "  SSH_HOST: $SSH_HOST"
        echo "  SSH_PORT: $SSH_PORT"
        echo "  SSH_KEY:  $SSH_KEY"
        ;;
    --test)
        echo "🧪 Test de connexion SSH..."
        ssh -i "$SSH_KEY" -p "$SSH_PORT" -o ConnectTimeout=5 -o BatchMode=yes "$SSH_USER@$SSH_HOST" exit
        if [ $? -eq 0 ]; then
            echo "✅ Connexion SSH réussie"
        else
            echo "❌ Échec de la connexion SSH"
        fi
        ;;
    *)
        main
        ;;
esac