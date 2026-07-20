# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Backend
- `CurrentUserExtension` (`src/ApiPlatform/`) - scopes every user-owned API Platform collection and item query to the authenticated user, and throws `AccessDeniedException` when an owned resource is queried with no user rather than returning the full table. Ships with an empty `OWNED_RESOURCES` list; unit-tested. `architecture.md` and `symfony.md` had mandated this class as a security invariant without it existing.

- `symfony/monolog-bundle` - the project had no logger at all. The prod handler writes JSON to `php://stderr`, so application errors surface in `docker logs` next to everything else.

#### Infrastructure
- Security headers on both nginx configs - HSTS (30 days), `X-Content-Type-Options`, `Referrer-Policy`, `server_tokens off`, plus `X-Frame-Options` on the frontend only. Declared at server level so a certbot renewal cannot strip them.

### Changed

#### Conventions
- Added `.context/coding-conventions/security.md` - trust boundaries, Clerk JWT trust model, data isolation, webhook signature rules, secrets in committed `.env` files, CORS regex, error-response leakage, rate limiting, nginx headers, frontend guards. Wired into the workflow entrypoint and into the context-loading step of `/spec`, `/dev`, `/implement`, `/review-changes`, and `/review-spec-implementation`.
- Added an "Absolute Directive" section at the top of `.context/coding-conventions/global.md` - the foundation every other rule, command, and spec is expected to already embody: think before coding, simplicity first (7-rung decision ladder), surgical changes with root-cause bug fixes, goal-directed execution, plus an explicit "never simplify away" list. Adapted from `andrej-karpathy-skills` and `ponytail`.
- Introduced the `shortcut:` comment marker for deliberate simplifications with a known ceiling and upgrade path.
- Reworked "Library Usage" to arbitrate a new dependency by size and risk domain instead of a blanket "prefer a library".
- `.context/ai-workflow-entrypoint.md` now points at the Absolute Directive before any other reading.

#### Infrastructure
- Dockerized the frontend: `frontend/Dockerfile` (multi-stage `dev` / `builder` / `runner`), `frontend/.dockerignore`, `next.config.ts` `output: "standalone"`, `package.json` pinned `packageManager`.
- Moved `docker-compose.yml`, `docker-compose.override.yml`, `docker-compose.prod.yml` from `backend/` to the project root; they now orchestrate backend + frontend together.
- Rewrote `infra/deploy.sh` (build-before-swap, `--env-file ./backend/.env`, zero downtime) and `infra/first-deploy.sh` to deploy the frontend via Docker instead of PM2.
- Updated `.context/infra.md`, `.context/architecture.md`, `.context/coding-conventions/global.md`, `README.md`, `INIT.md` to reflect the Docker-only frontend setup.

### Removed

#### Backend
- `phpunit.xml.dist` - dead config. PHPUnit 12 reads `phpunit.dist.xml`, which was already the one in effect and is stricter (`failOnDeprecation`, `failOnNotice`, `failOnWarning`).

### Fixed

#### Backend
- Clerk webhook accepted unlimited replays of any captured request - `svix-timestamp` was read but never validated, so a recorded `user.deleted` event stayed valid forever. Signature verification moved out of `ClerkController` into `ClerkSignatureService` with a ±5 minute freshness window, unit-tested against replay, tampering, message-id swapping, and missing headers.
- `BackupController` returned the raw exception message alongside its `500`, which can carry the database host, the database user, or the S3 bucket path. It now returns a generic message and logs the exception through Monolog instead.
- `composer.lock` had drifted behind `composer.json` - `doctrine/doctrine-migrations-bundle` was locked at 3.7.0 while `^4.0` was declared. Re-resolving as part of the Monolog install corrected it. `aws/aws-sdk-php` was also out of alphabetical order despite `sort-packages: true`.

### Security

#### Backend
- Cleared all 4 advisories reported by `composer audit`, every one of them reached transitively through `aws/aws-sdk-php`:
  - `guzzlehttp/guzzle` 7.10.5 → 7.15.1 - CVE-2026-55767 (dot-only cookie domains match all hosts) and CVE-2026-55568 (silent HTTPS proxy downgrade to cleartext)
  - `guzzlehttp/psr7` 2.10.4 → 2.13.0 - CVE-2026-55766 (CRLF injection in HTTP start-line serialization)
  - `mtdowling/jmespath.php` 2.8.0 → 2.9.2 - CVE-2026-54133 (code injection via unescaped function names)
  - `guzzlehttp/promises` 2.4.1 → 2.5.1 - not itself vulnerable, but guzzle 7.12+ requires `^2.5`, so it was the actual blocker holding the whole chain back
- `composer audit` now reports no advisories.

## [0.1.0] - 2026-04-08

### Added

#### Backend
- Symfony 7 + API Platform 3 skeleton with JWT auth (LexikJWT)
- User entity (UUID PK, email, password, googleId, roles, timestamps)
- PasswordResetToken entity (SHA-256 hashed token, expiry, usedAt)
- AuthController: register, Google OAuth, forgot-password, reset-password endpoints
- EmailService: password reset email via AWS SES (HTTP API, us-east-2)
- GoogleAuthService: Google ID token verification against tokeninfo API
- PasswordResetService: token generation, SHA-256 hashing, email dispatch
- MercureService: Mercure JWT generation and event publishing
- Docker setup: dev + prod compose files, multi-stage Dockerfile, nginx, supervisor, entrypoint
- PHPUnit unit tests: PasswordResetServiceTest, GoogleAuthServiceTest

#### Frontend
- Next.js 16 (App Router, src/ layout, TypeScript, Tailwind v4, shadcn/ui)
- Clerk auth: `<SignIn />` / `<SignUp />` hosted UI, webhook sync to Symfony User entity
- Auth pages: login, register (Clerk hosted UI)
- Dashboard layout with sidebar and `useDashboardLayout` (active nav detection)
- `useUIStore` (Zustand): sidebar, theme
- `lib/api.ts`: fetch wrapper with JWT injection and 401 redirect
- `src/middleware.ts`: Clerk auth guard + next-intl i18n routing
- next-intl i18n: `[locale]` App Router segment, `en` + `fr` locales
- `useMercure` hook: SSE subscription with Mercure JWT
- Homepage placeholder: hero, features grid, nav, footer
- Jest test setup + unit tests: useAuthStore, useLoginPage
- System font stack (no external font loading)

#### Infrastructure
- `infra/deploy.sh`: git pull + docker compose up + pnpm build + pm2 reload
- `infra/first-deploy.sh`: bootstrap script for initial server setup
- `infra/nginx/[project].domain.com`: frontend reverse proxy config
- `infra/nginx/b.[project].domain.com`: backend reverse proxy config
- `infra/nginx/setup.sh`: nginx config install + certbot SSL
- `.github/workflows/ci.yml`: PHPUnit + typecheck + Jest on push/PR
- `.github/workflows/deploy.yml`: SSH deploy on push to main
- `.github/dependabot.yml`: monthly updates for npm, composer, actions
- `docs/PRD.md` and `docs/architecture.md`: placeholder templates
