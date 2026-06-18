#!/bin/bash
# Run once on the server to bootstrap the environment before CI/CD takes over.
set -e

REPO_URL="https://github.com/[owner]/[repo].git"
DEPLOY_PATH="/home/www/[project-name]"
COMPOSE="docker compose --env-file ./backend/.env -f docker-compose.yml -f docker-compose.prod.yml"

echo "==> Setting up deployment directory..."
mkdir -p "$DEPLOY_PATH"
cd "$DEPLOY_PATH"

if [ ! -d ".git" ]; then
    git clone "$REPO_URL" .
fi

echo "==> Building and starting backend + frontend..."
$COMPOSE up -d --build --wait
$COMPOSE exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

echo "==> First deploy complete! Run bash infra/nginx/setup.sh from the project root to configure nginx + SSL."
