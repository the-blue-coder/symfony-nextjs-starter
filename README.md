> **To initialize a project from this boilerplate, clone the repo and run:**
>
> ```
> /init-project
> ```

# symfony-nextjs-starter

A production-ready fullstack SaaS starter built to be used with Claude Code CLI, following a Spec-Driven Development workflow.
Symfony 7 + API Platform backend, Next.js 16 frontend, full auth, Docker, CI/CD.

## Stack

- **Backend**: Symfony 7, API Platform 3, PostgreSQL, Redis, Symfony Messenger, AWS SES, Mercure
- **Frontend**: Next.js 16, Clerk, next-intl, TanStack Query v5, Zustand, React Hook Form + Zod, Tailwind v4, shadcn/ui
- **Infra**: Docker (backend), PM2 (frontend), nginx + certbot, GitHub Actions

## What's included

### Auth
- Clerk: email + password, Google OAuth, magic links - configured in the Clerk dashboard
- Webhook sync (user.created / user.updated / user.deleted) → Symfony User entity
- JWT guard on Symfony side (JWKS from Clerk)

### Frontend
- Auth pages: login, register (Clerk hosted UI via `<SignIn />` / `<SignUp />`)
- Dashboard layout with sidebar and active nav detection
- `useUIStore` (Zustand) - auth state handled by Clerk hooks
- `src/middleware.ts` - Clerk auth guard + next-intl i18n routing
- `useMercure` hook for real-time SSE subscriptions
- next-intl i18n: `[locale]` App Router segment, `en` + `fr` locales
- Homepage placeholder (hero, features, nav, footer)
- Dark mode support with FOUC prevention

### Backend
- JWT firewall + user provider
- API Platform resources with UUID PKs
- Doctrine ORM with lifecycle callbacks (createdAt / updatedAt)
- Symfony Messenger + Redis for async jobs

### Conventions
- Coding conventions in `.context/coding-conventions/` (global, frontend, backend)
- State management patterns (TanStack Query, Zustand, RHF + Zod)
- API contract rules (API Platform, hydra, UUIDs)
- Error handling standards
- Form patterns (loading states, edit spinners, navigation guards)
- UI patterns (DeleteModal, pending rows)

### Infrastructure
- Docker split setup: `docker-compose.yml` (local) + `docker-compose.prod.yml` (prod overrides)
- `infra/deploy.sh` + `infra/first-deploy.sh`
- nginx configs + certbot setup script
- GitHub Actions: `ci.yml` (PHPUnit + typecheck + Jest) + `deploy.yml` (SSH deploy on push to main)
- Dependabot (npm, composer, actions)

### Tests
- PHPUnit unit tests: `PasswordResetService`, `GoogleAuthService`
- Jest unit tests: `useAuthStore`, `useLoginPage`

## Usage

See [NEW_PROJECT_GUIDELINES.md](./NEW_PROJECT_GUIDELINES.md) for the full checklist when starting a new project from this starter.

## Local development

### Prerequisites

- Docker
- Node.js + pnpm

### Backend

```bash
cd backend
docker compose up -d    # starts postgres + redis + backend - API at http://localhost:8000
```

### Frontend

```bash
cd frontend
cp .env .env.local      # override values for local dev
pnpm install
pnpm dev                # http://localhost:3000
```

## AI Development

This starter uses **Spec-Driven Development (SDD)**. Context and conventions live in `.context/` - start there.

### Two paths

**Quick fixes** (bugs, typos, small corrections - ≤ 3 files, no new feature): write code directly, no pipeline needed.

**Features**: follow the pipeline:

```
/planify → /dev → /verify
```

| Command | What it does |
| --- | --- |
| `/planify` | Clarifies requirements, writes a spec in `.context/feature-specs/` |
| `/dev` | Implements a spec, checks off acceptance criteria |
| `/verify` | Verifies every criterion against the code, marks the spec done |

Specs live in `.context/feature-specs/` as markdown files with `status: todo / in-progress / done`.

## Built with

[Claude Code CLI](https://claude.ai/code)
