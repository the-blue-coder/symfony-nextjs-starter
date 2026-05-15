#!/bin/bash
set -e

DEPLOY_PATH="/home/www/[project-name]"

echo "==> Pulling latest code..."
cd "$DEPLOY_PATH"
git pull origin main

echo "==> Deploying backend..."
cd "$DEPLOY_PATH/backend"
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build --wait --remove-orphans
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Deploying frontend..."
cd "$DEPLOY_PATH/frontend"
pnpm install --frozen-lockfile
pnpm build
pm2 reload [project-name] --update-env 2>/dev/null || pm2 start pnpm --name "[project-name]" -- start

echo "==> Done!"
