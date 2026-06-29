# Infrastructure & Deployment

## Overview

> Two possible topologies - keep only the one this project uses, delete the other.

**Topology A - single instance per service (default):**

- **Server**: Contabo VPS, Ubuntu
- **Web server**: nginx + certbot (SSL)
- **Backend**: Docker (PHP-FPM + nginx) - `b.[project].domain.com` on port [XXXX]
- **Frontend**: Docker (Next.js standalone) - `[project].domain.com` on port [XXXX]
- **Deploy path**: `/home/www/[project-name]`

**Topology B - rolling zero-downtime deploy (2 instances per service):**

- **Server**: Contabo VPS, Ubuntu
- **Web server**: nginx + certbot (SSL)
- **Backend**: Docker (PHP-FPM + nginx) - `b.[project].domain.com`, 2 instances behind nginx: port [XXXX] (`backend_a`) and port [XXXX] (`backend_b`)
- **Frontend**: Docker (Next.js standalone) - `[project].domain.com`, 2 instances behind nginx: port [XXXX] (`frontend_a`) and port [XXXX] (`frontend_b`)
- **Deploy path**: `/home/www/[project-name]`
- nginx load-balances both instances of each service via a static `upstream` block with passive health checks; `infra/deploy.sh` updates one instance at a time with a health check (`GET /api/health`) before moving to the next, so a deploy never interrupts service. See `INIT.md` §A3b for the full setup (kept until `INIT.md` is deleted in §A9 - copy the relevant notes here before that if this topology is in use).

## Docker (backend + frontend)

**Topology A - single instance:**

- `docker-compose.yml` (root) - shared service definitions for postgres + redis + backend + frontend.
- `docker-compose.override.yml` (root) - **local only** - ports, bind mounts, hot reload.
- `docker-compose.prod.yml` (root) - **prod only** - production overrides (env, volumes, restart policies, build targets).
- Backend API exposed on **port 8000** locally (http://localhost:8000); frontend on **port 3000** (http://localhost:3000).
- `vendor/` **must** be in `backend/.dockerignore` - never copy Composer dependencies into the build context.
- `frontend/Dockerfile` is multi-stage (`dev` / `builder` / `runner`): local dev runs the `dev` target with the source bind-mounted (hot reload); prod builds the Next.js **standalone** output (`output: "standalone"` in `next.config.ts`) and runs it from the minimal `runner` stage.
- `NEXT_PUBLIC_*` vars are **build-time**: `next build` bakes them in from the committed `frontend/.env`. `frontend/.env.local` is excluded via `.dockerignore` so prod builds never bake in local dev values.

**Local dev (single command, from the project root):**

```bash
docker compose up    # postgres + redis + backend + frontend - API at http://localhost:8000, app at http://localhost:3000
```

**Topology B - rolling zero-downtime deploy:**

- `docker-compose.yml` (root) - postgres + redis + `backend_a`/`backend_b` + `frontend_a`/`frontend_b`, defined via `x-backend`/`x-frontend` YAML anchors. Only `backend_a`/`frontend_a` carry a `build:` block - `backend_b`/`frontend_b` just reference the resulting image tag. **Never add `build:` to both instances of a pair** - building the same image tag concurrently races (`failed to solve: image "...:latest": already exists`).
- `docker-compose.override.yml` (root) - **local only** - ports/bind mounts/hot reload for the `_a` instance only. Local dev doesn't need 2 instances, only prod does.
- `docker-compose.prod.yml` (root) - **prod only** - production overrides for both instances of both services, via anchors.
- Backend on ports **[XXXX]**/**[XXXX]** (`_a`/`_b`), frontend on **[XXXX]**/**[XXXX]** (`_a`/`_b`) in prod; locally just **8000**/**3000** (single instance).
- `vendor/` **must** be in `backend/.dockerignore` - never copy Composer dependencies into the build context.
- `frontend/Dockerfile` is multi-stage (`dev` / `builder` / `runner`), same as Topology A.
- `NEXT_PUBLIC_*` vars are **build-time**, same as Topology A.

**Local dev (from the project root) - only the `_a` instances run:**

```bash
docker compose up postgres redis backend_a frontend_a    # API at http://localhost:8000, app at http://localhost:3000
```

**Production** - `infra/deploy.sh` uses both compose files, plus `--env-file` since Compose does not read a service's `env_file` for `${VAR}` interpolation in the prod overrides:

```bash
docker compose --env-file ./backend/.env -f docker-compose.yml -f docker-compose.prod.yml
```

> nginx on the **host** (not dockerized) proxies each domain to the port published by its container (frontend / backend). Run `infra/first-deploy.sh` once before `infra/nginx/setup.sh` so the containers are up and listening on those ports first.

## Env Variables

- `.env` files are **committed** and hold production values.
- `.env.local` files are **gitignored** and override values for local dev.
- Backend env vars must be declared in **both** `backend/.env` AND `docker-compose.prod.yml`'s (root) `environment:` section (as `${VAR}`).
- After adding a new backend env var → recreate: `docker compose up -d --force-recreate backend`.
- `.env.example` must always be up to date.

## Nginx

- Configs named after the domain:
  - `infra/nginx/[project].domain.com` - frontend
  - `infra/nginx/b.[project].domain.com` - backend
- Setup script: `infra/nginx/setup.sh` - installs config + runs certbot.
- **Topology B only (rolling deploy)**: each config defines an `upstream` block listing both instances (`max_fails=1 fail_timeout=5s` per server, `proxy_next_upstream error timeout`, `proxy_connect_timeout 2s`), and `proxy_pass` targets the upstream name instead of `http://localhost:<port>`. Config is static and never reloaded by the deploy script - nginx's passive health checks route around whichever instance is currently down.

## Deploy Scripts

- `infra/deploy.sh` - triggered via GitHub Actions on push to `main`.
- `infra/first-deploy.sh` - run **once** on the server to set up the environment (clone repo if not already present, build and start backend + frontend containers); the git clone must be conditional: `[ ! -d ".git" ] && git clone ...`.
- **Topology A (single instance)** - deploys are **build-before-swap**: `infra/deploy.sh` builds new images while the old containers keep serving, then `up -d` only recreates the services whose image changed - minimal downtime, and a broken build (`set -e`) never touches the running site.
- **Topology B (rolling deploy)** - `infra/deploy.sh` builds once, then rolls `backend_a` → `backend_b` → `frontend_a` → `frontend_b` one at a time: `up -d --no-deps <instance>`, poll that instance's own `/api/health` directly (bypassing nginx) until 200 or `HEALTH_TIMEOUT` (60s), only then move to the next. If an instance never becomes healthy, the script aborts (`exit 1`) and leaves the other, still-serving instance untouched. No separate migration step - `docker/entrypoint.sh` already migrates on every backend container start, and the sequential roll order guarantees the schema is migrated before the second instance serves. `infra/first-deploy.sh` starts all 4 instances directly (`up -d --build --wait`) - nothing is live yet, so no rolling logic is needed.

## GitHub Actions

- **Secrets**: `CONTABO_HOST`, `CONTABO_USER`, `CONTABO_SSH_PRIVATE_KEY`.
- **Naming convention**: workflow name is `Deploy to Contabo` - never `Deploy on Contabo`. Applies to `name:` (top-level), `jobs.<job>.name:`, and `steps.- name:`.

## Known Gotchas

### Docker nginx - DNS caching after backend container recreation

Applies only when nginx runs in its **own container** (separate from the PHP-FPM backend, with `fastcgi_pass backend:9000`). Nginx resolves the `backend` hostname at startup and caches the IP. When the backend container is recreated on deploy it gets a new Docker-assigned IP, and nginx keeps the stale one → 502 until nginx itself is restarted.

Fix: use Docker's internal resolver with a short TTL **and** a variable upstream (the variable is what forces re-resolution at request time — a literal hostname in `fastcgi_pass` ignores the resolver):

```nginx
resolver 127.0.0.11 valid=5s ipv6=off;

location ~ ^/index\.php(/|$) {
    set $upstream backend:9000;
    fastcgi_pass $upstream;
    ...
}
```

Does **not** apply to the single-container setup (`fastcgi_pass 127.0.0.1:9000`) used by the boilerplate's default architecture.

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
docker compose -f docker-compose.yml -f docker-compose.prod.yml \
  exec -T -e APP_ENV=test -e APP_SECRET=test-secret backend \
  php bin/phpunit tests/Unit --no-coverage
```

> Topology B (rolling deploy): replace `backend` with `backend_a`.

**Frontend** (from `frontend/`):

```bash
pnpm lint
pnpm test --ci --passWithNoTests
```
