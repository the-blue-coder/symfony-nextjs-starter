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
2. **Project slug** - kebab-case identifier (e.g. `my-app`)
3. **Objective** - one or two sentences describing what the app does and who it's for
4. **Frontend domain** - e.g. `my-app.example.com`
5. **Backend domain** - e.g. `b.my-app.example.com`
6. **GitHub repo** - Create the repo at **https://github.com/new** (use the project slug as the repo name), then paste the HTTPS clone URL (e.g. `https://github.com/acme/my-app.git`)
7. **Ports**:
   - Local: frontend (default `3000`) & backend (default `8000`)
   - Prod: frontend & backend
8. **Search engine indexing** - should the app be publicly indexed? (yes / no - sets `robots` meta tag and `robots.txt`)
9. **Mercure** - does this project need real-time push features? (default: **yes** - included in the boilerplate)
10. **Authentication mode** - how is this project secured?
    - **Clerk** (default) - full user-facing auth: sign-in, sign-up, OAuth, webhooks, JWT guard. Keep everything as-is and proceed to §A4b.
    - **Admin secrets only** - no public user auth; the backend is protected by a static API key or HTTP Basic Auth. All Clerk code will be stripped (see §A3).
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
15. **Mailer sender address (`MAILER_FROM`)** - the address all transactional emails will be sent from (default: `contact@madainsight.com`).
    > ⚠️ Before proceeding, make sure this address is:
    > 1. **Verified in AWS SES** as a sender identity (Identities → verify the exact address or its domain).
    > 2. **Configured as a mailbox in cPanel** (the email panel dedicated to this project) so the address can actually receive replies.
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

Then in the repo settings → **Secrets and variables → Actions**, add:

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
| `[project-slug]` | Slug - in `.context/infra.md` (PM2 process name), `backend/docker-compose.yml` and `backend/docker-compose.prod.yml` (`name:` field) |
| `[project].domain.com` | Frontend domain - in `.context/infra.md`, `backend/.env`, `backend/.env.example`, `infra/nginx/setup.sh` |
| `b.[project].domain.com` | Backend domain - same files as above |
| `[FRONTEND_PORT]` | Prod frontend port - in `.context/infra.md`, `infra/first-deploy.sh`, `infra/nginx/setup.sh` |
| `[BACKEND_PORT]` | Local backend port - in `frontend/.env.example` |
| `[PROD_BACKEND_PORT]` | Prod backend port - in `.context/infra.md`, `backend/docker-compose.prod.yml`, `infra/nginx/setup.sh`, AND in `infra/nginx/b.<domain>` (`proxy_pass http://localhost:<port>;`) |
| `[owner]/[repo]` | GitHub repo - in `infra/first-deploy.sh` |

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
- **Clerk** → leave as-is; proceed to §A4b for Clerk setup instructions.
- **Admin secrets only** → strip all Clerk code:
  - **Frontend**:
    - `frontend/src/app/[locale]/layout.tsx` → remove `<ClerkProvider>` wrapper and its import.
    - `frontend/src/middleware.ts` → remove `clerkMiddleware` and Clerk imports (or delete the file if it only contained Clerk logic).
    - Delete `frontend/src/app/[locale]/(auth)/login/` and `frontend/src/app/[locale]/(auth)/register/`.
    - `frontend/.env` + `frontend/.env.example` → remove all `NEXT_PUBLIC_CLERK_*` and `CLERK_*` vars.
    - `frontend/package.json` → remove `@clerk/nextjs`, then run `pnpm install`.
  - **Backend**:
    - Delete `backend/src/Security/ClerkAuthenticator.php`.
    - Delete `backend/src/Controller/ClerkWebhookController.php`.
    - `backend/config/packages/security.yaml` → remove the Clerk JWT firewall and replace with your chosen auth mechanism (API key header check, HTTP Basic, etc.).
    - `backend/.env` + `backend/.env.example` → remove `CLERK_JWKS_URI` and `CLERK_WEBHOOK_SECRET`.

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

## A4. Generate secrets

Generate and write directly into `backend/.env` - do not ask the user to run these:

```bash
openssl rand -hex 32      # → APP_SECRET in backend/.env
```

## A4b. Clerk setup

> **Skip this step if §A1.10 = Admin secrets only.**

The user must:

1. Create a Clerk application at **https://dashboard.clerk.com** → "Add application".
2. Copy keys into `frontend/.env`:
   - `NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY` - from API Keys page.
   - `CLERK_SECRET_KEY` - from API Keys page.
3. Copy the JWKS URL into `backend/.env` as `CLERK_JWKS_URI`:
   - In Clerk dashboard → API Keys → **Advanced** → copy the JWKS endpoint (format: `https://<your-clerk-domain>/.well-known/jwks.json`).
4. Create a webhook for user sync:
   - Clerk dashboard → Webhooks → Add endpoint.
   - URL: `https://<backend-domain>/api/clerk/webhook`
   - Events to subscribe: `user.created`, `user.updated`, `user.deleted`
   - Copy the **Signing Secret** into `backend/.env` as `CLERK_WEBHOOK_SECRET`.

---

## A5. Install dependencies

Run these commands automatically - do not ask the user:

```bash
# Backend
cd backend
docker compose up -d
docker compose exec backend composer install
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

# Frontend
cd frontend
pnpm install
```

---

## A6. Fill in remaining environment values

Ask the user to provide values for the following - these require manual setup outside the repo.

**AWS - IAM users (S3 + SES)**

1. **S3 backup user** - ask for the full S3 URI of the backup folder (e.g. `s3://bizinfo/backups/db/<project-slug>/`). Do not guess - it must already exist in AWS.
   - Parse: bucket = everything between `s3://` and the first `/`; prefix = the rest.
   - Fill `backend/.env`: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_S3_BACKUP_BUCKET` (format: `bucket/prefix`).
2. **SES mailer user** - fill `backend/.env`: `MAILER_FROM` (from §A1.15), `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` for SES.

**Remaining `backend/.env` values (if §A1.10 = Clerk):**
- `CLERK_JWKS_URI`, `CLERK_WEBHOOK_SECRET` - from §A4b (Clerk setup)

**Remaining `frontend/.env` values (if §A1.10 = Clerk):**
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

**Step 2 - Bootstrap the server (run once via SSH)**

```bash
ssh root@207.180.238.155
git clone https://github.com/<owner>/<project-slug>.git /home/www/<project-slug>
cd /home/www/<project-slug>
bash infra/first-deploy.sh
bash infra/nginx/setup.sh
```

`first-deploy.sh` clones the repo, installs dependencies, builds the frontend, and starts PM2.
`nginx/setup.sh` installs nginx configs and runs certbot for SSL.

**Step 3 - Verify**

- Frontend: `https://<frontend-domain>` → should load the app.
- Backend: `https://<backend-domain>/api` → should return the API Platform JSON-LD entrypoint (or `/` if API Platform was removed).

From this point on, every push to `main` triggers an automatic deploy via GitHub Actions.

---

## A8. Clean up

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
10. **Authentication mode** - is it Clerk (check for `@clerk/nextjs` in `package.json`, `ClerkProvider` in layout, Clerk env vars) or admin secrets only? Ask only if ambiguous.
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
- Fill in real domains, ports, deploy path, PM2 process name - from existing scripts or §B2 answers.

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

**GitHub Actions secrets** - verify in repo settings → Secrets and variables → Actions:

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

## B6. Clean up

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
