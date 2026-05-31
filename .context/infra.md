# Infrastructure & Deployment

## Overview

- **Server**: Contabo VPS, Ubuntu
- **Web server**: nginx + certbot (SSL)
- **Backend**: Docker (PHP-FPM + nginx) - `b.[project].domain.com` on port [XXXX]
- **Frontend**: PM2, Next.js - `[project].domain.com` on port [XXXX]; PM2 process name: `[project_slug]_frontend`
- **Deploy path**: `/home/www/[project-name]`

## Docker (backend only)

- `backend/docker-compose.yml` - **local only** - postgres + redis + backend (PHP-FPM + nginx).
- `backend/docker-compose.prod.yml` - **prod only** - production overrides (env, volumes, restart policies).
- Backend API exposed on **port 8000** locally (http://localhost:8000).
- `vendor/` **must** be in `backend/.dockerignore` - never copy Composer dependencies into the build context.
- Frontend runs locally with `pnpm dev` (no Docker).

**Local dev:**

```bash
cd backend
docker compose up    # postgres + redis + backend - API at http://localhost:8000
```

**Production** - `infra/deploy.sh` uses both compose files:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml
```

## Env Variables

- `.env` files are **committed** and hold production values.
- `.env.local` files are **gitignored** and override values for local dev.
- Backend env vars must be declared in **both** `backend/.env` AND `backend/docker-compose.prod.yml`'s `environment:` section (as `${VAR}`).
- After adding a new backend env var → recreate: `docker compose up -d --force-recreate backend`.
- `.env.example` must always be up to date.

## Nginx

- Configs named after the domain:
  - `infra/nginx/[project].domain.com` - frontend
  - `infra/nginx/b.[project].domain.com` - backend
- Setup script: `infra/nginx/setup.sh` - installs config + runs certbot.

## Deploy Scripts

- `infra/deploy.sh` - triggered via GitHub Actions on push to `main`.
- `infra/first-deploy.sh` - run **once** on the server to set up the environment (clone repo if not already present, install deps, configure PM2, etc.); the git clone must be conditional: `[ ! -d ".git" ] && git clone ...`.

## GitHub Actions

- **Secrets**: `CONTABO_HOST`, `CONTABO_USER`, `CONTABO_SSH_PRIVATE_KEY`.
- **Naming convention**: workflow name is `Deploy to Contabo` - never `Deploy on Contabo`. Applies to `name:` (top-level), `jobs.<job>.name:`, and `steps.- name:`.

## Known Gotchas

### Nginx + Certbot - proxy headers stripped on renewal

The certbot renewal config uses `installer = nginx`, which rewrites the `location /` block on every `certbot renew` and strips all `proxy_set_header` directives.

Consequence: `X-Forwarded-For` missing → `$request->getClientIp()` returns the Docker bridge IP (e.g. `172.26.0.1`).

**Always do both on every project:**

1. The backend nginx `location /` block must contain:

   ```nginx
   proxy_http_version 1.1;
   proxy_set_header Host $host;
   proxy_set_header X-Real-IP $remote_addr;
   proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
   proxy_set_header X-Forwarded-Proto $scheme;
   client_max_body_size 20M;
   ```

2. Create `/etc/letsencrypt/renewal-hooks/deploy/restore-nginx-proxy-headers.sh` that re-applies these headers if missing, then reloads nginx.

## Test Commands (reference only - do not run automatically)

**Backend:**

```bash
docker compose -f backend/docker-compose.yml -f backend/docker-compose.prod.yml \
  exec -T -e APP_ENV=test -e APP_SECRET=test-secret backend \
  php bin/phpunit tests/Unit --no-coverage
```

**Frontend** (from `frontend/`):

```bash
pnpm lint
pnpm test --ci --passWithNoTests
```
