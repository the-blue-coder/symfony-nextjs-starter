#!/bin/bash
# Run once on the server to bootstrap the environment before CI/CD takes over.
set -e

REPO_URL="git@github.com:[owner]/[repo].git"
DEPLOY_PATH="/home/www/[project-name]"
PROJECT_NAME="[project-name]"
FRONTEND_PORT="[FRONTEND_PORT]"

echo "==> Setting up deployment directory..."
mkdir -p "$DEPLOY_PATH"
cd "$DEPLOY_PATH"

if [ ! -d ".git" ]; then
    git clone "$REPO_URL" .
fi

echo "==> Starting backend..."
cd "$DEPLOY_PATH/backend"
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php bin/console lexik:jwt:generate-keypair --skip-if-exists
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Starting frontend..."
cd "$DEPLOY_PATH/frontend"
pnpm install --frozen-lockfile
pnpm build

PORT="$FRONTEND_PORT" pm2 start pnpm --name "$PROJECT_NAME-frontend" -- start
pm2 save

echo "==> First deploy complete! Run bash infra/nginx/setup.sh from the project root to configure nginx + SSL."
