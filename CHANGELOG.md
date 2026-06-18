# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

#### Infrastructure
- Dockerized the frontend: `frontend/Dockerfile` (multi-stage `dev` / `builder` / `runner`), `frontend/.dockerignore`, `next.config.ts` `output: "standalone"`, `package.json` pinned `packageManager`.
- Moved `docker-compose.yml`, `docker-compose.override.yml`, `docker-compose.prod.yml` from `backend/` to the project root; they now orchestrate backend + frontend together.
- Rewrote `infra/deploy.sh` (build-before-swap, `--env-file ./backend/.env`, zero downtime) and `infra/first-deploy.sh` to deploy the frontend via Docker instead of PM2.
- Updated `.context/infra.md`, `.context/architecture.md`, `.context/coding-conventions/global.md`, `README.md`, `INIT.md` to reflect the Docker-only frontend setup.

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
