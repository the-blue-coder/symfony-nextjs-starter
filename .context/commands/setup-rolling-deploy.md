---
description: "Add (or scale up) rolling zero-downtime deploy on a project already in production - multiple instances per service behind nginx, rolled one at a time with health checks"
---

Set up rolling zero-downtime deploy on a project that is **already live in production** with a single instance per service - or add more instances to a project that already has rolling deploy enabled. Reference implementation: `freexcomics`'s `.context/feature-specs/001-rolling-zero-downtime-deploy.md` and `INIT.md` §A3b in this boilerplate.

This command never invents project-specific values (domains, env vars, slugs) - it reads them from the project's own files and extends them.

---

## Step 1 - Detect current state

Read, in this project:

- `docker-compose.yml` - does it already define `backend_a`/`backend_b` (etc.) and `frontend_a`/`frontend_b`, or plain `backend`/`frontend`?
- `docker-compose.prod.yml` - existing prod ports per service/instance.
- `infra/nginx/<frontend-domain>` and `infra/nginx/b.<backend-domain>` - is there already an `upstream` block, or a direct `proxy_pass http://localhost:<port>`?
- `infra/deploy.sh` - single `up -d --wait` swap, or already a `roll_instance` loop?
- `backend/src/Controller/HealthController.php` and `frontend/src/app/api/health/route.ts` - already present?
- `.context/infra.md` - which topology does it currently document?

From this, determine:

- **Current instance count per service** (1 if plain `backend`/`frontend`, else count the `_a`/`_b`/`_c`... suffixes present).
- **Current ports per existing instance** (from `docker-compose.prod.yml` and the nginx configs).
- **Project slug, frontend domain, backend domain** (from `docker-compose.yml`'s `name:` and the domains already in `backend/.env` / `.context/infra.md`).

---

## Step 2 - Ask the user

> How many instances per service do you want **in total**? (currently: `<N>` - default: `<N + 1>`, minimum 2)

Compute `instancesToAdd = target - current`. If `target <= current`, stop and tell the user to use `/teardown-rolling-deploy` instead (that command removes instances, this one only adds).

Assign suffixes for the new instances in order, continuing the existing letter sequence: if instances `a`/`b` already exist and 1 is being added, the new one is `c`; if 2 are being added, `c` and `d`; etc.

---

## Step 3 - Pick ports for the new instances

Read the centralized port registry on the server. **You (the agent) run this yourself via `ssh contabo`** (host alias already configured locally, do not use `ssh root@<ip>`) - this is a read-only lookup, not a deploy action, so no separate user confirmation is needed before reading it:

```bash
ssh contabo "cat /home/www/app.ports.txt"
```

For each new instance, propose the next free port that continues the existing numbering without gaps - e.g. if backend ports go up to `8007`, the next is `8008`, not an arbitrary jump like `8010` (frontend and backend ports are tracked in separate sequences in the registry, under `FRONTENDS` and `BACKENDS`). Confirm the proposed ports with the user before proceeding.

---

## Step 4 - Update `docker-compose.yml`

**If this is the first activation** (currently 1 instance per service): lift the entire existing `backend`/`frontend` service body into `x-backend`/`x-frontend` YAML anchors (preserve every existing `environment`, `volumes`, `depends_on`, `env_file` entry exactly as-is - do not drop or rewrite project-specific config). Then create:

```yaml
x-backend: &backend
  # ... the full body that used to be under `backend:`, minus `build:`
  networks:
    - network

services:
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
```

Same shape for `frontend_a`/`frontend_b`. **Only the first instance (`_a`) keeps the `build:` block** - every other instance just references the image tag `_a` builds. Building the same tag from two services concurrently races (`failed to solve: image "...:latest": already exists`).

**If rolling deploy is already active**: just add the new instance(s) (`backend_c`, `frontend_c`, ...), each `<<: *backend` / `<<: *frontend` with its own `container_name`, no `build:` block.

---

## Step 5 - Update `docker-compose.override.yml`

Local dev never needs more than the `_a` instance - leave it pointing only at `_a` (ports, bind mounts, hot reload). Do not add local ports for new instances.

---

## Step 6 - Update `docker-compose.prod.yml`

**If this is the first activation**: same lift-into-anchor treatment as Step 4, applied to the prod overrides (`restart`, `build.target`, prod `environment`/`volumes`). Apply the anchor to every instance; only `_a` overrides `build.target`.

**Either way**, add the new instance(s) with their assigned port:

```yaml
backend_c:
  <<: *backend-prod
  ports:
    - "[NEW_BACKEND_PORT]:80"
  environment:
    MERCURE_URL: http://mercure-mercure-1/.well-known/mercure   # only if this project uses Mercure
  networks:
    - default
    - mercure_default                                            # only if this project uses Mercure
```

```yaml
frontend_c:
  <<: *frontend-prod
  ports:
    - "[NEW_FRONTEND_PORT]:3000"
```

---

## Step 7 - Update nginx configs

`infra/nginx/<frontend-domain>` and `infra/nginx/b.<backend-domain>`:

**If this is the first activation**: wrap the current `proxy_pass http://localhost:<port>;` into an `upstream` block with passive health checks, and point `proxy_pass` at the upstream name instead:

```nginx
upstream [project_slug]_backend {
    server 127.0.0.1:[BACKEND_PORT_A] max_fails=1 fail_timeout=5s;
    server 127.0.0.1:[BACKEND_PORT_B] max_fails=1 fail_timeout=5s;
}
```

```nginx
location / {
    proxy_pass http://[project_slug]_backend;
    proxy_connect_timeout 2s;
    proxy_next_upstream error timeout;
    # ... keep every existing proxy_set_header / proxy_http_version / client_max_body_size line as-is
}
```

Same pattern for the frontend config (`upstream [project_slug]_frontend { ... }`), keeping the existing `Upgrade`/`Connection` headers for websocket support.

**Either way**, add one `server 127.0.0.1:<port> max_fails=1 fail_timeout=5s;` line per new instance to the relevant `upstream` block.

> **No dynamic nginx reload is needed for future deploys** - the config is static, and nginx's passive health checks already route around whichever instance is down. But **this one-time config change must be deployed manually now** (see Step 11) - it doesn't happen via `infra/deploy.sh`.

---

## Step 8 - Update `infra/deploy.sh`

**If this is the first activation**, replace the single `up -d --wait --remove-orphans` swap with a rolling sequence:

```bash
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
        echo "==> ERROR: $service did not become healthy within ${HEALTH_TIMEOUT}s. Aborting - the other instances are untouched and still serving."
        exit 1
    fi

    echo "==> $service is healthy."
}
```

Keep the existing `git pull` + `$COMPOSE build` steps, then call `roll_instance` once per instance, in order (`backend_a`, `backend_b`, ... then `frontend_a`, `frontend_b`, ...). Remove the old `doctrine:migrations:migrate` step if present - migrations already run automatically inside `backend/docker/entrypoint.sh` on every backend container start, and rolling `_a` before `_b` already serializes this safely. **Do not add a separate one-off migration container** - if the prod Dockerfile has an `ENTRYPOINT` that ends in `exec supervisord`, a `docker compose run --rm backend_a php bin/console doctrine:migrations:migrate` never exits and hangs CI.

Since the project is already live, this conversion needs **one unavoidable swap**: the existing single-instance container occupies the port `_a` will reuse. Add this one-time cleanup immediately before the first `roll_instance backend_a ...` call (not earlier - keep the downtime window to just that one roll, not the whole build phase):

```bash
docker rm -f [project_slug]_backend 2>/dev/null || true
roll_instance backend_a [BACKEND_PORT_A]
roll_instance backend_b [BACKEND_PORT_B]
```

Same for frontend (`docker rm -f [project_slug]_frontend` right before `roll_instance frontend_a ...`).

**If rolling deploy is already active**: just append `roll_instance backend_c [NEW_BACKEND_PORT]` (and the frontend equivalent) after the existing calls - no cleanup needed, this is purely additive and never touches the existing instances.

---

## Step 9 - Update `infra/first-deploy.sh`

Update it for consistency (in case the server is ever rebuilt from scratch) even though it won't run now: it should start **all** instances directly with `up -d --build --wait`, no rolling logic - nothing is live yet in that scenario.

---

## Step 10 - Add health endpoints (skip whichever already exists)

**Backend** - `GET /api/health`, no auth, `200 {"status":"ok"}` after a DB ping, `503 {"status":"error","message":...}` otherwise. Follow this project's "raw SQL never in services" convention (check `.context/coding-conventions/symfony.md` or `backend/`'s actual convention doc) - put the DB ping in a repository, not the service:

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

If the project has a global request listener guarding a route prefix (e.g. an admin-secret check on `/api/admin/*`), verify it does **not** match `/api/health`.

**Frontend** - `GET /api/health`, always `200 {"status":"ok"}` once the process is up (liveness only):

`frontend/src/app/api/health/route.ts`:
```ts
import { NextResponse } from "next/server";

export async function GET() {
  return NextResponse.json({ status: "ok" });
}
```

---

## Step 11 - Update the port registry on the server

Append one line per new instance (format: `<port>  <domain>`), keeping each section (`FRONTENDS` / `BACKENDS`) sorted ascending by port. The suffix reflects the instance's position for that domain - first instance has no suffix, second is `(2nd)`, third `(3rd)`, fourth `(4th)`, etc. (the registry already has examples, e.g. `3010  freexcomics.com (2nd)`).

**You (the agent) run this yourself via `ssh contabo`** - editing this shared coordination file is a normal part of this command's workflow, not a remote/destructive action requiring a separate confirmation gate. You already confirmed the port numbers with the user in Step 3; just write them now, no extra round-trip needed.

```bash
ssh contabo "sed -i '/^<previous-highest-port-line>/a <new-port>  <domain> (<ordinal>)' /home/www/app.ports.txt"
```

Confirm the resulting file with the user (for their awareness, not as a permission gate):

```bash
ssh contabo "cat /home/www/app.ports.txt"
```

---

## Step 12 - Update `.context/infra.md`

Document the new instance count and ports. If this project's `infra.md` still describes a single-instance topology, replace it with the rolling-deploy description (ports, `upstream` mechanics, deploy sequence) - see the "Topology B" template in this boilerplate's own `.context/infra.md` for the wording to reuse.

---

## Step 13 - Deploy

Tell the user the exact sequence (do not run the push / prod-deploy / nginx-reload steps below yourself without confirmation - unlike the port registry edit in Step 11, these touch the live site and its git history):

1. Commit and push the changes to `main` - this triggers `infra/deploy.sh` via GitHub Actions, which performs the rolling swap described in Step 8 (including the one-time `docker rm -f` if this was the first activation).
2. **One-time manual nginx update** (not handled by `deploy.sh`): SSH to the server and install the new nginx configs, then reload:
   ```bash
   ssh contabo
   cd /home/www/<project-slug>
   cp infra/nginx/<frontend-domain> /etc/nginx/sites-available/<frontend-domain>
   cp infra/nginx/b.<backend-domain> /etc/nginx/sites-available/b.<backend-domain>
   nginx -t && systemctl reload nginx
   ```
3. Verify: hit both domains, confirm `GET https://<frontend-domain>/api/health` and `GET https://b.<backend-domain>/api/health` both return `200`.

---

## Step 14 - Report

Summarize: instance count before/after, ports added, files changed, and the exact manual steps from Step 13 the user still needs to run.
