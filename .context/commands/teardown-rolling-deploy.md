---
description: "Remove rolling zero-downtime deploy from a project, or scale it down - reverts to fewer instances per service (down to 1, plain single-instance, if fully undone)"
---

Undo rolling zero-downtime deploy on a project - either remove it entirely (back to a single instance per service) or scale it down by removing the highest-numbered instance(s) while keeping at least 2.

This command never invents project-specific values - it reads the project's actual files and reduces them.

---

## Step 1 - Detect current state

Read, in this project:

- `docker-compose.yml` - which instances exist (`backend_a`/`backend_b`/`backend_c`...)?
- `docker-compose.prod.yml` - the port of each existing instance.
- `infra/nginx/<frontend-domain>` and `infra/nginx/b.<backend-domain>` - the `upstream` block listing all instance ports.
- `infra/deploy.sh` - the `roll_instance` calls, one per instance.

If no `_a`/`_b` suffixes are found, stop and tell the user:
> Rolling deploy isn't configured on this project. Nothing to undo.

---

## Step 2 - Ask the user

> This project currently runs `<N>` instances per service. Do you want to fully disable rolling deploy (back to 1 instance), or just remove the highest instance(s) and keep at least 2?

- **Full revert** (back to 1 instance) - the `_a` instance survives and is renamed back to the plain `backend`/`frontend` service name, matching the boilerplate's default shape. All other instances (`_b`, `_c`, ...) are removed.
- **Partial removal** - ask how many instances to remove (e.g. "remove 1" goes from 3 to 2). The highest-lettered instance(s) are removed first (`_c` before `_b`); never remove `_a`.

State clearly which instances (letters + ports) will be removed, and confirm before proceeding - this is destructive on the server (containers will be stopped and removed).

---

## Step 3 - Update `docker-compose.yml`

**Partial removal**: delete the removed instances' service blocks. Leave the `x-backend`/`x-frontend` anchors and remaining instances untouched.

**Full revert**: collapse back to plain `backend`/`frontend` services - take the `x-backend`/`x-frontend` anchor body (which still holds every project-specific `environment`/`volumes`/`env_file` entry) and inline it directly under a single `backend:` / `frontend:` key with `container_name: [project_slug]_backend` (no suffix) and the `build:` block restored. Delete the anchors and the removed `_b`/`_c`/... service entries.

---

## Step 4 - Update `docker-compose.override.yml`

**Partial removal**: no change needed - local override already only targets `_a`.

**Full revert**: rename `backend_a` / `frontend_a` back to `backend` / `frontend` to match the collapsed service name from Step 3.

---

## Step 5 - Update `docker-compose.prod.yml`

**Partial removal**: delete the removed instances' blocks.

**Full revert**: same collapse as Step 3 - single `backend`/`frontend` service with the prod overrides (`restart`, `build.target`, prod env/volumes) inlined directly, anchors removed.

---

## Step 6 - Update nginx configs

`infra/nginx/<frontend-domain>` and `infra/nginx/b.<backend-domain>`:

**Partial removal**: delete the removed instance(s)' `server 127.0.0.1:<port> max_fails=1 fail_timeout=5s;` line(s) from the `upstream` block. Leave the rest (including `proxy_pass http://[project_slug]_backend;`) untouched.

**Full revert**: remove the `upstream` block entirely and change `proxy_pass http://[project_slug]_backend;` back to `proxy_pass http://localhost:[BACKEND_PORT_A];` (same port the surviving `_a`/now-plain instance uses). Also remove `proxy_connect_timeout 2s;` and `proxy_next_upstream error timeout;` (those only make sense with multiple upstream members) - keep every other existing `proxy_set_header` / `proxy_http_version` / `client_max_body_size` line as-is.

> This nginx change must be deployed manually (see Step 11) - it is not picked up by `infra/deploy.sh`.

---

## Step 7 - Update `infra/deploy.sh`

**Partial removal**: delete the `roll_instance` call(s) for the removed instance(s). Keep the rest of the rolling sequence and the `wait_for_health` / `roll_instance` helper functions (still needed for the surviving instances).

**Full revert**: replace the entire rolling sequence (helper functions + `roll_instance` calls) with the boilerplate's default build-before-swap:

```bash
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
```

`--remove-orphans` here is what actually stops and removes the now-undeclared `_b`/`_c` containers on the next deploy.

---

## Step 8 - Update `infra/first-deploy.sh`

**Partial removal**: no change needed.

**Full revert**: replace `$COMPOSE up -d --build --wait` rolling-all-instances comment/logic (if any was added) with the plain boilerplate version - single `backend`/`frontend`, no rolling logic, `$COMPOSE exec -T backend php bin/console doctrine:migrations:migrate --no-interaction` after `up -d --build --wait`.

---

## Step 9 - Health endpoints (optional)

The `/api/health` endpoints (backend `HealthController`/`HealthService`/`HealthRepository`, frontend `app/api/health/route.ts`) are harmless without rolling deploy and don't need to be removed - they're a reasonable thing to keep as a general liveness check. Ask the user if they want them deleted as part of a complete revert; default to keeping them unless asked.

---

## Step 10 - Update `.context/infra.md`

**Partial removal**: update the instance count and port list.

**Full revert**: replace the rolling-deploy ("Topology B") description with the single-instance ("Topology A") description - see this boilerplate's own `.context/infra.md` for the wording to reuse.

---

## Step 11 - Remove the freed ports from the server's port registry

List exactly which lines will be removed (port + domain + suffix, e.g. `8008  b.<backend-domain> (2nd)`) and confirm with the user which lines those are before touching the server - this is the only confirmation needed; once confirmed, **you (the agent) run the ssh commands yourself via `ssh contabo`** (host alias already configured locally, do not use `ssh root@<ip>`). Editing this shared coordination file is a normal part of this command's workflow, not a remote/destructive action requiring a separate permission gate.

```bash
ssh contabo "cat /home/www/app.ports.txt"
```

Remove the matching line(s):

```bash
ssh contabo "sed -i '/<port>  <domain>.*/d' /home/www/app.ports.txt"
```

If other instances remain (partial removal) and their ordinal suffixes are now wrong (e.g. removing the `(2nd)` instance while a `(3rd)` survives), renumber the survivors' suffixes so they stay consecutive (the surviving instance that was `(3rd)` becomes `(2nd)`, etc.) - edit those lines too.

Confirm the resulting file with the user (for their awareness, not as a permission gate):

```bash
ssh contabo "cat /home/www/app.ports.txt"
```

---

## Step 12 - Deploy

Tell the user the exact sequence (do not run the push / prod-deploy / nginx-reload steps below yourself without confirmation - unlike the port registry edit in Step 11, these touch the live site and its git history):

1. Commit and push the changes to `main` - `infra/deploy.sh` will recreate the changed instances and, on full revert, `--remove-orphans` stops and removes the now-undeclared `_b`/`_c` containers.
2. **One-time manual nginx update**:
   ```bash
   ssh contabo
   cd /home/www/<project-slug>
   cp infra/nginx/<frontend-domain> /etc/nginx/sites-available/<frontend-domain>
   cp infra/nginx/b.<backend-domain> /etc/nginx/sites-available/b.<backend-domain>
   nginx -t && systemctl reload nginx
   ```
3. Verify the site still loads and (if instances remain) `GET /api/health` on the surviving instance(s) still returns `200`.

---

## Step 13 - Report

Summarize: instance count before/after, exactly which ports were freed and removed from the registry, files changed, and the manual steps from Step 12 the user still needs to run.
