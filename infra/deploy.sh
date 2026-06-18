#!/bin/bash
set -e

DEPLOY_PATH="/home/www/[project-name]"
COMPOSE="docker compose --env-file ./backend/.env -f docker-compose.yml -f docker-compose.prod.yml"

echo "==> Pulling latest code..."
cd "$DEPLOY_PATH"
git pull origin main

echo "==> Building new images (current containers keep serving - no downtime)..."
$COMPOSE build

echo "==> Recreating only the services whose image changed..."
$COMPOSE up -d --wait --remove-orphans

echo "==> Running database migrations..."
$COMPOSE exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Done!"
