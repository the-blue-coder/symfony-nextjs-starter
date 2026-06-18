# Project Initialization

> **For the AI assistant**: Read this file fully before asking anything. Determine the path (A or B) from the first question below, then follow only that path. Do not skip steps.
>
> **Language**: All file contents, code, comments, and generated text must be written in **English** - regardless of the language the user writes in.

---

## 0. Determine the path

Ask the user:

> Is this a **fresh project** from the boilerplate, or an **existing project** you're bringing into this structure?

- **Fresh** → follow **Path A** below.
- **Existing** → follow **Path B** below.

---

---

# Path A - Fresh project from the boilerplate

> **Prerequisites**: Ask the user to make sure **Docker Desktop is running** before proceeding.

---

## A1. Collect project info

Before touching any file, collect:

1. **Project name** - display name (e.g. `My App`)
2. **Project slug** - snake_case identifier (e.g. `my_app`)
3. **Objective** - one or two sentences describing what the app does and who it's for
4. **Frontend domain** - e.g. `my-app.example.com`
5. **Backend domain** - e.g. `b.my-app.example.com`
6. **GitHub repo** - Create the repo at **https://github.com/new** — use the **project slug in kebab-case** as the repo name (e.g. `my-app`), then paste the HTTPS clone URL (e.g. `https://github.com/the-blue-coder/my-app.git`)
7. **Ports**:
   - Local: frontend (default `3000`) & backend (default `8000`) - single instance, even if rolling deploy is enabled below (no need to duplicate the dev setup).
   - **Rolling zero-downtime deploy** - should this project run 2 instances per service (frontend + backend) behind nginx, rolled one at a time with a health check before moving to the next, so deploys never interrupt traffic? (default: **no** - only add this complexity if the project expects significant production traffic)
     - **No** (default) → Prod: 1 port for frontend, 1 port for backend. Continue with the rest of Path A unchanged.
     - **Yes** → Prod: 4 ports - frontend `_a` / frontend `_b` / backend `_a` / backend `_b`. Follow §A3b in addition to the steps below.
   - **Before assigning prod ports** (either case): read the centralized port registry on the server to pick free ports that continue the existing numbering without gaps:
     ```bash
     ssh root@207.180.238.155 "cat /home/www/app.ports.txt"
     ```
     E.g. if existing backend ports go up to `8007`, the next free one is `8008` - not an arbitrary jump like `8010`.
   - **After assigning prod ports**, append them to the registry (format: `<port>  <domain>`, one per line):
     ```bash
     ssh root@207.180.238.155 "echo '<port>  <domain>' >> /home/www/app.ports.txt"
     ```
     - Single instance (default): one line per service, e.g. `3005  my-app.example.com` and `8004  b.my-app.example.com`.
     - Rolling deploy (if enabled): one line per instance, with the **second** instance's domain suffixed `(2nd)` - e.g. `3010  my-app.example.com (2nd)` and `8008  b.my-app.example.com (2nd)` (a third instance, if ever needed, would use `(3rd)`, then `(4th)`, etc.). The first instance's line has no suffix.
8. **Search engine indexing** - should the app be publicly indexed? (yes / no - sets `robots` meta tag and `robots.txt`)
9. **Mercure** - does this project need real-time push features? (default: **yes** - included in the boilerplate)
10. **Authentication mode** - Clerk is the only option. Choose the registration mode:
    - **Clerk with registration** (default) - public sign-in + sign-up. Users can create their own account.
    - **Clerk without registration** - sign-in only. No public sign-up. Users are created via the Clerk dashboard. Delete the register route (see §A3).
11. **API Platform** - does this project need API Platform? (default: **yes** - included in the boilerplate)
    - **Use API Platform when**: you're building a public or partner-facing REST/GraphQL API, you need automatic OpenAPI docs, or external clients consume your backend.
    - **Skip API Platform when**: the backend only serves this one frontend (no external consumers), you prefer full control over controllers and serialization, or the project is simple enough that API Platform's overhead isn't worth it.
12. **Internationalization (i18n)** - does this project need multi-language support? (default: **yes** - next-intl is included in the boilerplate)
    - **Use i18n when**: the app targets multiple locales, has a language switcher, or content needs to be translated.
    - **Skip i18n when**: the app is single-language only and localization is not planned.
13. **Google Analytics** - does this project need Google Analytics? (default: **no**)
    - If yes: go to [analytics.google.com](https://analytics.google.com), create a property, and copy the Measurement ID (format: `G-XXXXXXXXXX`).
14. **Microsoft Clarity** - does this project need Microsoft Clarity (heatmaps + session recordings)? (default: **no**)
    - If yes: go to [clarity.microsoft.com](https://clarity.microsoft.com), create a project, and copy the Project ID.
15. **Mailer sender address (`MAILER_FROM`)** - the address all transactional emails will be sent from. Use `[project_slug]@madainsight.com` (e.g. `my_app@madainsight.com`).
    > ⚠️ Before proceeding, set up the mailbox and DNS records:
    >
    > **Step 1 — cPanel (NTMada)**
    > - Log in to the cPanel for `madainsight.com`.
    > - Go to **Email Accounts** → Create a new mailbox: `[project_slug]@madainsight.com`.
    >
    > **Step 2 — DNS (NameSilo)**
    > Add the following three records for `madainsight.com` (replace `[project_slug]` with the actual value, e.g. `my_app`):
    >
    > | Name | Type | Value | TTL |
    > |---|---|---|---|
    > | `mail.[project_slug]` | A | `91.204.209.49` | 3603 |
    > | `[project_slug]` | MX | `mail.[project_slug].madainsight.com` | 3603 |
    > | `[project_slug]` | TXT | `v=spf1 a mx ip4:91.204.209.49 ~all` | 3603 |
    >
    > **Step 3 — AWS SES**
    > - Go to [SES Identities](https://us-east-2.console.aws.amazon.com/ses/home?region=us-east-2#/identities) → **Create identity** → Email address → enter `[project_slug]@madainsight.com` → click **Create identity**.
    > - AWS sends a verification email to that address; click the link to confirm.
16. **UI design** - five sub-questions:
    - **Theme mode**: dark only / light only / light + dark (system preference)?
    - **Primary accent color**: hex value or description (e.g. `#6366f1` indigo, `#0ea5e9` sky blue). If unsure, say so - defaults will be used.
    - **Typography**: font(s) to use. Either provide a URL to extract fonts from, or name them directly (e.g. `Inter`, `Geist Sans + Geist Mono`). If unsure, Geist defaults will be kept.
    - **Layout**: what is the top-level layout? (e.g. sidebar + main content, top navbar + content, full-viewport canvas, etc.)
    - **Reference site(s)**: would you like to base the colors and overall UI style on one or more existing sites? (yes / no - if yes, provide the URL(s))

---

## A2. Create the GitHub repository

Wire up the remote:

```bash
git remote set-url origin https://github.com/<owner>/<project-slug>.git
```

Then add secrets at **https://github.com/the-blue-coder/[project-slug]/settings/secrets/actions/new**:

| Secret | Value |
|---|---|
| `CONTABO_HOST` | `207.180.238.155` |
| `CONTABO_USER` | `root` |
| `CONTABO_SSH_PRIVATE_KEY` | see below |

**How to get `CONTABO_SSH_PRIVATE_KEY`:**

```bash
ssh root@207.180.238.155 "cat ~/.ssh/id_rsa"
```

Copy the full output (including `-----BEGIN ... PRIVATE KEY-----` / `-----END ... PRIVATE KEY-----`) and paste it as the secret value.

These must be in place before the first push to `main` so CI/CD can deploy immediately.

---

## A3. Replace all placeholders

Use the answers from §A1 to replace every placeholder across the repo.

| Placeholder | Replace with |
|---|---|
| `[Project Name]` | Display name - in `.context/project-overview.md` (title + `App Name`), `frontend/src/app/layout.tsx`, `frontend/src/app/page.tsx`, `frontend/src/app/(dashboard)/layout.tsx` |
| `[project-name]` | Slug - in `.context/infra.md` (deploy path), `.github/workflows/deploy.yml`, `infra/deploy.sh`, `infra/first-deploy.sh`, `infra/nginx/setup.sh` |
| `[project_slug]` | Slug - in `.context/infra.md`, `docker-compose.yml`, `docker-compose.override.yml` and `docker-compose.prod.yml` (`name:` and container names) |

> **Docker network naming**: the internal network is always called `network` in compose files. Docker Compose automatically prefixes it with the project name (`name:` field), producing `[project_slug]_network`. Never name the network `[project_slug]_network` directly — that produces a double-prefix like `[project_slug]_[project_slug]_network`.
| `[project].domain.com` | Frontend domain - in `.context/infra.md`, `backend/.env`, `backend/.env.example`, `infra/nginx/setup.sh` |
| `b.[project].domain.com` | Backend domain - same files as above |
| `[FRONTEND_PORT]` | Prod frontend port - in `.context/infra.md`, `docker-compose.prod.yml`, `infra/nginx/setup.sh` |
| `[PROD_BACKEND_PORT]` | Prod backend port - in `.context/infra.md`, `docker-compose.prod.yml`, `infra/nginx/setup.sh`, AND in `infra/nginx/b.<domain>` (`proxy_pass http://localhost:<port>;`) |
| `[owner]/[repo]` | GitHub repo - in `infra/first-deploy.sh` |

> **If rolling zero-downtime deploy was enabled (§A1.7)**: the two port rows above don't apply - use `[FRONTEND_PORT_A]`/`[FRONTEND_PORT_B]` and `[BACKEND_PORT_A]`/`[BACKEND_PORT_B]` instead, per §A3b. In `infra/nginx/setup.sh`, point the temporary HTTP-only bootstrap configs at the `_a` ports (`FRONTEND_PORT_A` / `BACKEND_PORT_A`) - the final configs installed after certbot (step 4 of §A3b) already load-balance both instances via `upstream`.

**Let's Encrypt email** (already hardcoded in `infra/nginx/setup.sh` as `jd.rakotoarison@gmail.com`): used for SSL renewal notifications. Each domain gets its own certificate - `setup.sh` makes two separate `certbot --nginx` calls (one per domain). Do NOT combine them into a single SAN cert.

**Fill in `.context/project-overview.md`**:
- Replace `[Project Name]` with the display name.
- Replace the overview paragraph with the objective from §A1.3.
- Fill in Goals, Core User Flow, Features, Scope, and Success Criteria - leave placeholders for anything not yet defined.

**Set `APP_NAME` / `NEXT_PUBLIC_APP_NAME`:**
- `frontend/.env` + `frontend/.env.example` → add `NEXT_PUBLIC_APP_NAME=<Project Name>`
- `backend/.env` + `backend/.env.example` → add `APP_NAME=<Project Name>`

**Rename nginx config files:**
- `infra/nginx/[project].domain.com` → `infra/nginx/<frontend-domain>`
- `infra/nginx/b.[project].domain.com` → `infra/nginx/<backend-domain>`

Update their contents with the real domains and prod ports.

**Update `.context/infra.md`** with the real domains, ports, and deploy path.

**Fill in `.context/ui-context.md`** using §A1.16:
- **Theme**: chosen mode (dark only / light only / light + dark).
- **Typography**: if §A1.13 provides a URL → fetch it and extract the font stack. If fonts are named directly → use them. Otherwise keep Geist defaults.
- **Layout Patterns**: describe the layout from §A1.13.
- Leave unspecified sections as placeholders - they will be refined as the project evolves.

> `ui-context.md` is for design context only - layout patterns, typography, visual language. Color tokens belong in `.context/coding-conventions/tailwind.md` (see below), not here.

**Fill in `.context/coding-conventions/tailwind.md`** - color tokens:
- If the user provided reference site URL(s) in §A1.16 → fetch each URL, extract the dominant colors (background, text, primary accent, secondary accents), and use them to populate the token table.
- Otherwise → fill `--accent-primary` from §A1.16; leave other tokens as reasonable defaults.

**Update `README.md`**: replace title and description with project name, slug, and objective. Keep the stack, features, and usage sections.

**Set `CORS_ALLOW_ORIGIN` in `backend/.env` and `backend/.env.example`**:
Build the regex from the frontend domain (§A1.4) - allows both localhost (dev) and the production frontend:
```
^(https?://(localhost|127\.0\.0\.1)(:[0-9]+)?|https://<frontend-domain>)$
```
Example for `laoka.madainsight.com`:
```
CORS_ALLOW_ORIGIN=^(https?://(localhost|127\.0\.0\.1)(:[0-9]+)?|https://laoka\.madainsight\.com)$
```
Note: dots in the domain must be escaped as `\.`.

**Authentication mode (§A1.10)**:
- **Clerk with registration** → leave as-is; proceed to §A4b for Clerk setup instructions.
- **Clerk without registration** → proceed to §A4b, then delete the register route:
  - Delete `frontend/src/app/[locale]/(auth)/register/`.
  - Remove any link to the register page from the login page and the homepage.

**API Platform (§A1.11)**:
- **Yes** → leave as-is.
- **No** → strip it out:
  - `backend/composer.json` → remove `api-platform/core`, run `docker compose exec backend composer update`.
  - `backend/config/packages/api_platform.yaml` → delete.
  - `backend/config/routes/api_platform.yaml` → delete if it exists.
  - All entities in `backend/src/Entity/` → remove `#[ApiResource]` attribute and its `use` statement.

**i18n / next-intl (§A1.12)**:
- **Yes** → leave as-is.
- **No** → strip it out:
  - `frontend/package.json` → remove `next-intl`, then run `pnpm install`.
  - `frontend/next.config.ts` → remove the `withNextIntl` wrapper and its import.
  - `frontend/middleware.ts` → remove the i18n routing logic (or delete the file if it only contained that).
  - Delete `frontend/src/i18n/` (routing config, request config).
  - Delete `frontend/messages/` (locale JSON files).
  - Delete `frontend/src/app/[locale]/(dashboard)/settings/` - the settings section only contains the language switcher; without i18n there is nothing to configure there.
  - `frontend/src/app/[locale]/(dashboard)/DashboardLayoutClient.tsx` → remove the `{ href: "/settings", label: "Settings", icon: Settings }` entry from `navItems` and remove the `Settings` import from `lucide-react` if it is no longer used.
  - In all pages and layouts under `frontend/src/app/`, remove `locale` params, `useTranslations`, `getTranslations`, and any locale-prefixed route segments (`[locale]/`).

**Google Analytics (§A1.13)**:
- **No** → delete `frontend/src/components/tracking/GoogleAnalytics.tsx`, remove its import and `{GA_MEASUREMENT_ID && <GoogleAnalytics ... />}` from `frontend/src/app/[locale]/layout.tsx`, remove `GA_MEASUREMENT_ID` from `frontend/src/constants/app.ts`, and remove `NEXT_PUBLIC_GA_MEASUREMENT_ID` from `frontend/.env` and `frontend/.env.example`.
- **Yes** → fill `NEXT_PUBLIC_GA_MEASUREMENT_ID=<G-XXXXXXXXXX>` in `frontend/.env`.

**Microsoft Clarity (§A1.14)**:
- **No** → delete `frontend/src/components/tracking/MicrosoftClarity.tsx`, remove its import and `{CLARITY_PROJECT_ID && <MicrosoftClarity ... />}` from `frontend/src/app/[locale]/layout.tsx`, remove `CLARITY_PROJECT_ID` from `frontend/src/constants/app.ts`, and remove `NEXT_PUBLIC_CLARITY_PROJECT_ID` from `frontend/.env` and `frontend/.env.example`.
- **Yes** → fill `NEXT_PUBLIC_CLARITY_PROJECT_ID=<project-id>` in `frontend/.env`.

**SEO indexing (§A1.8)**:
- `frontend/src/app/layout.tsx` → set `robots`: `{ index: true, follow: true }` or `{ index: false, follow: false }`.
- `frontend/public/robots.txt` → `Allow: /` or `Disallow: /`.

**App icon and PWA manifest**:

Generate a custom icon based on the project's purpose and accent color (§A1.16):

1. **`frontend/src/app/icon.svg`** - design an SVG icon (512×512 viewBox) that fits the app's purpose. Use the accent color as background, white foreground. Next.js serves this automatically as the favicon for modern browsers.
2. **`frontend/public/manifest.webmanifest`** - fill in real values (name, short_name, description, theme_color from accent color):
   ```json
   {
     "name": "<Project Name>",
     "short_name": "<Project Name>",
     "description": "<one-line description>",
     "start_url": "/",
     "display": "standalone",
     "background_color": "#ffffff",
     "theme_color": "<accent-primary hex>",
     "icons": [{ "src": "/icon.svg", "sizes": "any", "type": "image/svg+xml", "purpose": "any maskable" }]
   }
   ```
3. **`frontend/src/app/[locale]/layout.tsx`** → add to `metadata`:
   ```ts
   manifest: "/manifest.webmanifest",
   themeColor: "<accent-primary hex>",
   ```
4. **`frontend/src/app/[locale]/page.tsx`** → update the homepage nav to show the icon alongside the app name:
   ```tsx
   <div className="flex items-center gap-2">
     <img src="/icon.svg" alt={APP_NAME} className="h-7 w-7 rounded-md" />
     <span className="font-semibold">{APP_NAME}</span>
   </div>
   ```

---

## A3b. Rolling zero-downtime deploy (only if enabled in §A1.7)

> Skip this entire section if the user answered "no" in §A1.7 - the boilerplate's default single-instance files already work as-is.

This reproduces the pattern validated in production on the `freexcomics` project (`.context/feature-specs/001-rolling-zero-downtime-deploy.md` in that repo): each service runs as 2 instances (`_a` / `_b`) behind nginx, updated one at a time with a health check before moving to the next, so a deploy never interrupts service.

**1. Rewrite `docker-compose.yml`** - split `backend` and `frontend` into `backend_a`/`backend_b` and `frontend_a`/`frontend_b`, sharing config via YAML anchors. Only the `_a` instance carries the `build:` block - if both instances build the same image tag concurrently, it races (`failed to solve: image "...:latest": already exists`); `_b` just references the tag `_a` already built:

```yaml
name: [project_slug]

x-backend: &backend
  image: [project_slug]_backend
  depends_on:
    postgres:
      condition: service_healthy
    redis:
      condition: service_healthy
  env_file:
    - ./backend/.env
  environment: &backend-environment
    DATABASE_URL: postgresql://${POSTGRES_USER:-app}:${POSTGRES_PASSWORD:-app}@postgres:5432/${POSTGRES_DB:-app}?serverVersion=16&charset=utf8
    MESSENGER_TRANSPORT_DSN: redis://redis:6379/messages
  networks:
    - network

x-frontend: &frontend
  image: [project_slug]_frontend
  networks:
    - network

services:
  postgres:
    # ... unchanged from the single-instance file

  redis:
    # ... unchanged from the single-instance file

  backend_a:
    <<: *backend
    container_name: [project_slug]_backend_a
    build:
      context: ./backend
      dockerfile: Dockerfile
      target: dev

  backend_b:
    <<: *backend
    container_name: [project_slug]_backend_b

  frontend_a:
    <<: *frontend
    container_name: [project_slug]_frontend_a
    build:
      context: ./frontend
      dockerfile: Dockerfile
      target: dev

  frontend_b:
    <<: *frontend
    container_name: [project_slug]_frontend_b

volumes:
  postgres_data:

networks:
  network:
    driver: bridge
```

**2. Rewrite `docker-compose.override.yml`** (local only) - only configure ports/volumes for the `_a` instance of each service. `_b` stays defined (for image/network parity) but unreachable locally - local dev only ever needs one instance:

```yaml
name: [project_slug]

services:
  backend_a:
    ports:
      - "8000:80"
    env_file:
      - ./backend/.env.local
    volumes:
      - ./backend:/var/www/html
      - /var/www/html/vendor

  frontend_a:
    ports:
      - "3000:3000"
    environment:
      WATCHPACK_POLLING: "true"
    volumes:
      - ./frontend:/app
      - /app/node_modules
      - /app/.next
```

Document in `.context/infra.md` (see step 6 below) that local dev only starts the `_a` instances: `docker compose up postgres redis backend_a frontend_a`.

**3. Rewrite `docker-compose.prod.yml`** - apply prod overrides (restart policy, build target, env) to both instances of each service via anchors, same shape as the single-instance file but doubled:

```yaml
name: [project_slug]

x-backend-prod: &backend-prod
  restart: unless-stopped

x-frontend-prod: &frontend-prod
  restart: unless-stopped
  env_file:
    - ./frontend/.env

services:
  postgres:
    container_name: [project_slug]_postgres
    restart: unless-stopped
    volumes:
      - /var/data/[project-slug]/postgres:/var/lib/postgresql/data

  redis:
    container_name: [project_slug]_redis
    restart: unless-stopped

  backend_a:
    <<: *backend-prod
    ports:
      - "[BACKEND_PORT_A]:80"
    build:
      target: prod
    environment:
      MERCURE_URL: http://mercure-mercure-1/.well-known/mercure
    networks:
      - default
      - mercure_default

  backend_b:
    <<: *backend-prod
    ports:
      - "[BACKEND_PORT_B]:80"
    environment:
      MERCURE_URL: http://mercure-mercure-1/.well-known/mercure
    networks:
      - default
      - mercure_default

  frontend_a:
    <<: *frontend-prod
    ports:
      - "[FRONTEND_PORT_A]:3000"
    build:
      target: runner

  frontend_b:
    <<: *frontend-prod
    ports:
      - "[FRONTEND_PORT_B]:3000"

networks:
  mercure_default:
    external: true
```

> Drop the `MERCURE_URL` override / `mercure_default` network from both backend services if Mercure was declined in §A1.9.

**4. Rewrite the nginx configs** - `infra/nginx/<frontend-domain>` and `infra/nginx/b.<backend-domain>` each get an `upstream` block listing both instances, with passive health checks. No dynamic reload is needed - the config is static and nginx routes around whichever member is down:

```nginx
upstream [project_slug]_frontend {
    server 127.0.0.1:[FRONTEND_PORT_A] max_fails=1 fail_timeout=5s;
    server 127.0.0.1:[FRONTEND_PORT_B] max_fails=1 fail_timeout=5s;
}

server {
    listen 80;
    server_name [project].domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name [project].domain.com;

    ssl_certificate /etc/letsencrypt/live/[project].domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/[project].domain.com/privkey.pem;

    location / {
        proxy_pass http://[project_slug]_frontend;
        proxy_connect_timeout 2s;
        proxy_next_upstream error timeout;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

```nginx
upstream [project_slug]_backend {
    server 127.0.0.1:[BACKEND_PORT_A] max_fails=1 fail_timeout=5s;
    server 127.0.0.1:[BACKEND_PORT_B] max_fails=1 fail_timeout=5s;
}

server {
    listen 80;
    server_name b.[project].domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name b.[project].domain.com;

    ssl_certificate /etc/letsencrypt/live/b.[project].domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/b.[project].domain.com/privkey.pem;

    client_max_body_size 20M;

    location / {
        proxy_pass http://[project_slug]_backend;
        proxy_connect_timeout 2s;
        proxy_next_upstream error timeout;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**5. Rewrite `infra/deploy.sh` and `infra/first-deploy.sh`**:

```bash
#!/bin/bash
set -e

DEPLOY_PATH="/home/www/[project-name]"
COMPOSE="docker compose --env-file ./backend/.env -f docker-compose.yml -f docker-compose.prod.yml"
HEALTH_TIMEOUT=60
HEALTH_INTERVAL=2

wait_for_health() {
    local port="$1"
    local elapsed=0

    until curl -sf "http://localhost:$port/api/health" > /dev/null; do
        elapsed=$((elapsed + HEALTH_INTERVAL))
        if [ "$elapsed" -ge "$HEALTH_TIMEOUT" ]; then
            return 1
        fi
        sleep "$HEALTH_INTERVAL"
    done
}

roll_instance() {
    local service="$1"
    local port="$2"

    echo "==> Rolling $service (port $port)..."
    $COMPOSE up -d --no-deps "$service"

    if ! wait_for_health "$port"; then
        echo "==> ERROR: $service did not become healthy within ${HEALTH_TIMEOUT}s. Aborting - the other instance is untouched and still serving."
        exit 1
    fi

    echo "==> $service is healthy."
}

echo "==> Pulling latest code..."
cd "$DEPLOY_PATH"
git pull origin main

echo "==> Building new images (current instances keep serving - no downtime)..."
$COMPOSE build

# Migrations run automatically inside docker/entrypoint.sh on every backend
# container start (before supervisord launches) - no separate migration step
# needed here. Rolling backend_a before backend_b already serializes this:
# backend_a's entrypoint applies pending migrations and only then starts
# serving, so backend_b never starts against a not-yet-migrated schema.

roll_instance backend_a [BACKEND_PORT_A]
roll_instance backend_b [BACKEND_PORT_B]

roll_instance frontend_a [FRONTEND_PORT_A]
roll_instance frontend_b [FRONTEND_PORT_B]

echo "==> Done!"
```

> **Critical pitfall - do not add a separate migration step.** A one-off container like `docker compose run --rm backend_a php bin/console doctrine:migrations:migrate` does not do what it looks like: the prod image's `ENTRYPOINT` (`backend/docker/entrypoint.sh`) ignores the command passed to `run` and always ends with `exec supervisord`, which never returns - the container never exits, `--rm` never fires, and CI times out. Migrations already run automatically in `entrypoint.sh` on every backend container start; the sequential roll order (`backend_a` healthy before `backend_b` starts) is enough to guarantee they're applied before the second instance serves.

> **Retrofitting an already-deployed single-instance project** (only relevant outside a fresh Path A init): add a one-time `docker rm -f [project_slug]_backend` / `[project_slug]_frontend` immediately before rolling `backend_a` / `frontend_a` respectively (not earlier) - removing the old container right before its replacement claims the port keeps the downtime window to just that one roll, instead of the entire build phase.

```bash
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

echo "==> Starting both instances of backend and frontend..."
# Nothing is live yet, so no rolling logic is needed - migrations run
# automatically inside docker/entrypoint.sh on each backend container start.
$COMPOSE up -d --build --wait

echo "==> First deploy complete! Run bash infra/nginx/setup.sh from the project root to configure nginx + SSL."
```

**6. Add health endpoints**:

- **Backend** - `GET /api/health`, no auth, `200 {"status":"ok"}` after a successful DB ping, `503 {"status":"error","message":...}` otherwise. Follow the project's "raw SQL never in services" convention (`.context/coding-conventions/symfony.md`) - the DB ping lives in a repository, not the service:

  `backend/src/Repository/HealthRepository.php`:
  ```php
  <?php

  declare(strict_types=1);

  namespace App\Repository;

  use Doctrine\DBAL\Connection;
  use Throwable;

  class HealthRepository
  {
      public function __construct(
          private Connection $connection,
      ) {
      }

      public function pingDatabase(): bool
      {
          try {
              $this->connection->executeQuery('SELECT 1');

              return true;
          } catch (Throwable) {
              return false;
          }
      }
  }
  ```

  `backend/src/Service/HealthService.php`:
  ```php
  <?php

  declare(strict_types=1);

  namespace App\Service;

  use App\Repository\HealthRepository;

  class HealthService
  {
      public function __construct(
          private HealthRepository $healthRepository,
      ) {
      }

      public function isDatabaseHealthy(): bool
      {
          return $this->healthRepository->pingDatabase();
      }
  }
  ```

  `backend/src/Controller/HealthController.php`:
  ```php
  <?php

  declare(strict_types=1);

  namespace App\Controller;

  use App\Service\HealthService;
  use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
  use Symfony\Component\HttpFoundation\JsonResponse;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\Routing\Attribute\Route;

  #[Route('/api/health')]
  class HealthController extends AbstractController
  {
      public function __construct(
          private HealthService $healthService,
      ) {
      }

      #[Route('', methods: ['GET'])]
      public function check(): JsonResponse
      {
          if (!$this->healthService->isDatabaseHealthy()) {
              return $this->json([
                  'status' => 'error',
                  'message' => 'Database connection failed.',
              ], Response::HTTP_SERVICE_UNAVAILABLE);
          }

          return $this->json(['status' => 'ok']);
      }
  }
  ```

  If the project has (or later adds) a global request listener that guards a route prefix (e.g. an admin-secret check on `/api/admin/*`), verify it does **not** match `/api/health` - the deploy script and nginx must be able to call it unauthenticated.

- **Frontend** - `GET /api/health`, always `200 {"status":"ok"}` once the process is up (liveness only, no upstream dependency to check):

  `frontend/src/app/api/health/route.ts`:
  ```ts
  import { NextResponse } from "next/server";

  export async function GET() {
    return NextResponse.json({ status: "ok" });
  }
  ```

**7. Update `.context/infra.md`** with the dual-instance topology, ports, and rolling deploy mechanics - see the "Rolling zero-downtime deploy" variant already documented in the template (delete the single-instance variant instead).

---

## A4. Generate secrets

Generate and write directly into `backend/.env` - do not ask the user to run these:

```bash
openssl rand -hex 32      # → APP_SECRET in backend/.env
```

## A4b. Clerk setup

First, create the `.env.local` files from their templates:

```bash
cp backend/.env.local.example backend/.env.local
cp frontend/.env.local.example frontend/.env.local
```

These files are gitignored and override their respective `.env` for local development. You will fill in the Clerk dev values in the steps below.

The user must:

> **Note (production):** For API keys, JWKS URL, webhook configuration, and DNS domain setup, refer to the **Cashpoint** project as a reference — it is already fully configured in production.

1. Create a Clerk application at **https://dashboard.clerk.com** → "Add application".
2. **Switch to the Production instance** before copying any keys - in the Clerk dashboard, use the environment toggle to switch from Development to Production. Using development keys (`pk_test_`) shows a "Development mode" badge on the sign-in page.
3. Copy **production** keys into `frontend/.env`:
   - `NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY` - must start with `pk_live_`.
   - `CLERK_SECRET_KEY` - must start with `sk_live_`.
4. For **local development**, switch to the **Development** instance in the Clerk dashboard and copy the development keys (`pk_test_` / `sk_test_`) into `frontend/.env.local` (gitignored - overrides `.env` locally). Production keys are domain-locked to the production domain and will fail on `localhost`.
5. Copy the JWKS URL into `backend/.env` as `CLERK_JWKS_URI`:
   - In Clerk dashboard → API Keys → **Advanced** → copy the JWKS endpoint (format: `https://<your-clerk-domain>/.well-known/jwks.json`).
   - **Critical for local dev**: the backend must also use the **Development** instance JWKS URL, otherwise it will reject tokens issued by the dev Clerk instance with 401. Copy the **Development** JWKS URL into `backend/.env.local` as `CLERK_JWKS_URI` (overrides the prod value locally).
6. Create a webhook for user sync:
   - Clerk dashboard → Webhooks → Add endpoint.
   - URL: `https://<backend-domain>/api/webhook/clerk`
   - Events to subscribe: `user.created`, `user.updated`, `user.deleted`
   - Copy the **Signing Secret** into `backend/.env` as `CLERK_WEBHOOK_SECRET`.
   - For **local development**, also copy the **Development** webhook signing secret into `backend/.env.local` as `CLERK_WEBHOOK_SECRET`.

---

## A5. Install dependencies

Run these commands automatically - do not ask the user:

```bash
# Backend + frontend (from the project root)
docker compose up -d
docker compose exec backend composer install
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

# Frontend - local node_modules for editor tooling (lint, type-check, IDE autocomplete);
# the app itself runs inside the frontend container, not via this install
cd frontend
pnpm install
```

---

## A6. Fill in remaining environment values

Ask the user to provide values for the following - these require manual setup outside the repo.

**AWS - IAM users (S3 + SES)**

Create both IAM users at **https://us-east-1.console.aws.amazon.com/iam/home?region=us-east-2#/users/create**.

1. **S3 backup user** (`s3__[project_slug]`):
   - Create user → add to group **s3_group**.
   - Security credentials → Create access key → description: `S3 - [Project name]`.
   - Ask for the full S3 URI of the backup folder (e.g. `s3://bizinfo/backups/db/<project-slug>/`). Do not guess - it must already exist in AWS.
   - Parse: bucket = everything between `s3://` and the first `/`; prefix = the rest.
   - Fill `backend/.env`: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_S3_BACKUP_BUCKET` (format: `bucket/prefix`).
2. **SES mailer user** (`ses__[project_slug]`):
   - Create user → add to group **ses_group**.
   - Security credentials → Create access key → description: `SES - [Project name]`.
   - Fill `backend/.env`: `MAILER_FROM` (from §A1.15), `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` for SES.

**Remaining `backend/.env` values:**
- `CLERK_JWKS_URI`, `CLERK_WEBHOOK_SECRET` - from §A4b (Clerk setup)

**Remaining `frontend/.env` values:**
- `NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY`, `CLERK_SECRET_KEY` - from §A4b (Clerk setup)

**Mercure (§A1.9)**:
> Shared hub at `mercure.madainsight.com` (port 4000). JWT secret already in `.env` - do not regenerate.

- **Yes** → leave `MERCURE_*` values as-is.
- **No** → remove all `MERCURE_*` vars from `backend/.env`, `backend/.env.example`, `frontend/.env`, `frontend/.env.example`.

---

## A7. First server deploy

**Step 1 - Push to `main`**

```bash
git add -A
git commit -m "chore: initialize project"
git push origin main
```

> The GitHub Actions deploy workflow will trigger but fail on this first push - the server isn't set up yet. That's expected.

> **Before cloning on the server**: make sure the app works correctly on local (`docker compose up`, login, main flows). Fix any issues before proceeding.

**Step 2 - Bootstrap the server (run once via SSH)**

```bash
ssh root@207.180.238.155
git clone https://github.com/<owner>/<project-slug>.git /home/www/<project-slug>
cd /home/www/<project-slug>
bash infra/first-deploy.sh
bash infra/nginx/setup.sh
```

`first-deploy.sh` clones the repo and builds + starts the backend and frontend containers via Docker Compose.
`nginx/setup.sh` installs nginx configs and runs certbot for SSL.

**Step 3 - Verify**

- Frontend: `https://<frontend-domain>` → should load the app.
- Backend: `https://<backend-domain>/api` → should return the API Platform JSON-LD entrypoint (or `/` if API Platform was removed).

From this point on, every push to `main` triggers an automatic deploy via GitHub Actions.

---

## A8. Configure automated backup

The project is now live. Set up the database backup.

**Step 1 - Generate and set the webhook secret**

Generate a secret using the **LastPass browser extension** (Generate → 32 chars, letters + numbers) and write it directly into `backend/.env` as `AWS_S3_BACKUP_WEBHOOK_SECRET`.

Commit and push so the secret is deployed to the server.

**Step 2 - Configure the backup workflow on n8n**

Go to **https://n8n.madainsight.com/workflow/PWe4ZS7OlR3KzoDa** and duplicate the workflow, then:

1. Tag it with the app name (e.g. `MyApp`).
2. Move it into the folder `Personal > [AppName]`.
3. Update the **HTTP Request** node:
   - URL: `https://<backend-domain>/api/webhook/backup`
   - Header `X-Backup-Secret`: the value of `AWS_S3_BACKUP_WEBHOOK_SECRET`
4. Activate the workflow.

---

## A9. Clean up

Remove boilerplate-only files and the initialization notice from `README.md`:

```bash
rm INIT.md
rm VERSIONS.md
rm .github/dependabot.yml
```

In `README.md`, remove the opening block:

```
> **To initialize a project from this boilerplate, clone the repo and give your AI agent this prompt:**
>
> ```
> Read INIT.md and follow every step in order.
> ```
```

> **Reminder:** Once the app is live, don't forget to set up the n8n backup workflow (§A8).

---

---

# Path B - Existing project

Use this path when bringing an existing project into this boilerplate's structure: wiring up the `.context/` files, CI/CD, and deployment - without re-bootstrapping what's already there.

---

## B1. Explore the existing codebase (silent)

Before asking anything, read and explore:
- `backend/` - identify the stack, entities, services, env vars, and what's already configured.
- `frontend/` - identify pages, components, auth setup, env vars.
- `infra/` - check if deploy scripts and nginx configs exist and are filled in.
- `backend/.env` and `frontend/.env` - note which values are already set vs. missing.
- `CLAUDE.md` / `AGENTS.md` - note if they already point to `.context/ai-workflow-entrypoint.md`.

---

## B2. Collect missing info

Ask only for what the codebase doesn't already reveal:

1. **Project name** - if not found in existing env vars or layout files.
2. **Project slug** - if not found in `docker-compose.yml` or deploy scripts.
3. **Objective** - one or two sentences describing what the app does and who it's for.
4. **Frontend domain** - if not found in existing `.env` or nginx configs.
5. **Backend domain** - same.
6. **Ports** - prod frontend and backend ports, if not found in infra scripts.
7. **GitHub repo URL** - to verify or set the remote.
8. **Search engine indexing** - yes / no, if `robots.txt` and `layout.tsx` metadata are not already set.
9. **Mercure** - is it used? (check existing `MERCURE_*` env vars - ask only if ambiguous).
10. **Authentication mode** - Clerk is always used. Determine the registration mode: check for a `register/` route under `(auth)/` — if present, it's **with registration**; if absent, it's **without registration**.
11. **API Platform** - is it used? (check `composer.json` and entities - ask only if ambiguous).
12. **i18n / next-intl** - is it used? (check `package.json` for `next-intl` and presence of `frontend/messages/` - ask only if ambiguous).
13. **Google Analytics** - is it used? (check `NEXT_PUBLIC_GA_MEASUREMENT_ID` in `.env` and presence of `GoogleAnalytics.tsx` - ask only if ambiguous).
14. **Microsoft Clarity** - is it used? (check `NEXT_PUBLIC_CLARITY_PROJECT_ID` in `.env` and presence of `MicrosoftClarity.tsx` - ask only if ambiguous).
15. **Mailer sender address** - if `MAILER_FROM` is not already set.
16. **UI design** (only if `.context/ui-context.md` doesn't already exist with real values):
    - Theme mode, primary accent color, typography (URL or font names), top-level layout.

Skip any question whose answer is already clear from the code.

---

## B3. Wire up `.context/` files

For each file below, check if it already exists and has real content. If it does, leave it. If it's missing or still has placeholder content, fill it in:

**`.context/project-overview.md`**
- Fill project name, objective, goals, core user flow, features - derived from the existing codebase and §B2 answers.

**`.context/architecture.md`**
- Verify or update the stack table, folder structure, and invariants to match what's actually in the repo.

**`.context/infra.md`**
- Fill in real domains, ports, deploy path - from existing scripts or §B2 answers.

**`.context/ui-context.md`**
- Fill in theme, typography, layout patterns - from existing styles or §B2.14. Design context only - no color token tables here.

**`.context/coding-conventions/tailwind.md`**
- Fill in color token table - from existing Tailwind config or CSS variables.

**`.context/progress-tracker.md`**
- Set **Current Phase** and **Completed** to reflect what's already built. List obvious next steps under **Next Up**.

**`CLAUDE.md`** and **`AGENTS.md`**
- If they don't already point to `.context/ai-workflow-entrypoint.md`, update them:
  `> Start here: .context/ai-workflow-entrypoint.md`

---

## B4. Audit and complete infrastructure

Check each item - only act on what's missing or incorrect:

**GitHub remote**
```bash
git remote -v
```
If wrong or missing: `git remote set-url origin https://github.com/<owner>/<slug>.git`

**GitHub Actions secrets** - verify at **https://github.com/the-blue-coder/[project-slug]/settings/secrets/actions/new**:

| Secret | Value |
|---|---|
| `CONTABO_HOST` | `207.180.238.155` |
| `CONTABO_USER` | `root` |
| `CONTABO_SSH_PRIVATE_KEY` | `ssh root@207.180.238.155 "cat ~/.ssh/id_rsa"` |

**Secrets in `.env` files** - check if these are already set. Generate and fill any that are missing:
```bash
openssl rand -hex 32      # APP_SECRET
```

**Remaining env values** - ask the user for any that are blank:
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_S3_BACKUP_BUCKET` (S3 backup user)
- `MAILER_FROM` and SES credentials
- `CORS_ALLOW_ORIGIN` - build from the frontend domain: `^(https?://(localhost|127\.0\.0\.1)(:[0-9]+)?|https://<frontend-domain>)$`

**`.env.example`** - verify it's up to date with all vars present in `.env` (values redacted).

---

## B5. Deploy (if not already live)

Ask the user: **Is the project already deployed and running on the server?**

- **Yes** → skip this step.
- **No** → follow the same deploy flow as §A7:
  ```bash
  git add -A
  git commit -m "chore: wire up project structure"
  git push origin main
  ```
  Then SSH to the server and run `infra/first-deploy.sh` + `infra/nginx/setup.sh`.

---

## B6. Configure automated backup

The project is now live. Set up the database backup.

**Step 1 - Generate and set the webhook secret**

Generate a secret using the **LastPass browser extension** (Generate → 32 chars, letters + numbers) and write it directly into `backend/.env` as `AWS_S3_BACKUP_WEBHOOK_SECRET`.

Commit and push so the secret is deployed to the server.

**Step 2 - Configure the backup workflow on n8n**

Go to **https://n8n.madainsight.com/workflow/PWe4ZS7OlR3KzoDa** and duplicate the workflow, then:

1. Tag it with the app name (e.g. `MyApp`).
2. Move it into the folder `Personal > [AppName]`.
3. Update the **HTTP Request** node:
   - URL: `https://<backend-domain>/api/webhook/backup`
   - Header `X-Backup-Secret`: the value of `AWS_S3_BACKUP_WEBHOOK_SECRET`
4. Activate the workflow.

---

## B7. Clean up

Delete any boilerplate-only files and the initialization notice from `README.md`:

```bash
rm -f INIT.md
rm -f VERSIONS.md
```

In `README.md`, remove the opening block:

```
> **To initialize a project from this boilerplate, clone the repo and give your AI agent this prompt:**
>
> ```
> Read INIT.md and follow every step in order.
> ```
```

> **Reminder:** Once the app is live, don't forget to set up the n8n backup workflow (§B6).
