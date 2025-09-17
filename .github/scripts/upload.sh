#!/bin/bash

set -euo pipefail

lftp -u ${USER},${PASSWORD} sftp://${HOST} <<EOF

set sftp:auto-confirm yes
set net:max-retries 2
set net:timeout 30

lcd $GITHUB_WORKSPACE
cd /var/www/emailing

mirror --reverse --only-newer --verbose \
  --exclude-glob .env \
  --exclude-glob .editorconfig \
  --exclude-glob .git/ \
  --exclude-glob .gitattributes \
  --exclude-glob .github/ \
  --exclude-glob .gitignore \
  --exclude-glob .vscode/ \
  --exclude-glob node_modules/ \
  --exclude-glob storage/logs/* \
  --exclude-glob storage/framework/sessions/* \
  --exclude-glob tests/ \
  --exclude-glob vendor/ \


bye
EOF

echo "✅ Upload terminé."
