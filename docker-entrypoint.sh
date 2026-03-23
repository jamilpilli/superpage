#!/bin/bash
set -e

# Gera o ficheiro .env a partir das variáveis de ambiente do container
cat > /var/www/html/.env << EOF
DB_HOST=${DB_HOST:-127.0.0.1}
DB_NAME=${DB_NAME:-superpage}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
APP_DEBUG=${APP_DEBUG:-false}
EOF

exec "$@"
