# Architecture

## Stack

| Layer          | Technology                        | Role                              |
| -------------- | --------------------------------- | --------------------------------- |
| Backend        | Symfony 8 + API Platform 4        | REST API (JSON-LD)                |
| Database       | PostgreSQL + Doctrine ORM         | Persistence                       |
| Cache          | Redis                              | Provisioned via Docker Compose, not yet wired into Symfony cache (see `backend/config/packages/cache.yaml`) |
| Async          | Symfony Messenger                 | Background jobs                   |
| Frontend       | Next.js 16 (App Router)           | UI - SSR + client                 |
| Styling        | Tailwind CSS v4 + shadcn/ui       | Design system                     |
| Server state   | TanStack Query v5                 | Fetch, cache, revalidate          |
| Client state   | Zustand                           | Global UI / auth state            |
| Forms          | React Hook Form + Zod             | Validation + submission           |
| Auth           | Clerk                             | Auth, JWT, webhooks               |
| i18n           | next-intl                         | Translations                      |

## Repo Structure

```
/
├── backend/                  - Symfony + API Platform
├── frontend/                 - Next.js 16 + pnpm
├── infra/                    - deploy scripts + nginx configs
├── docker-compose.yml        - shared service definitions (backend + frontend, both Docker)
├── docker-compose.override.yml - local overrides (ports, bind mounts, hot reload)
├── docker-compose.prod.yml   - prod overrides (env, restart policies, build targets)
└── CHANGELOG.md
```

## Frontend `src/` Tree (mandatory)

Initialize with `src/` (answer **Yes** to `create-next-app`'s "Would you like to use `src/` directory?").

```
src/
├── app/
│   ├── (auth)/              # Public route group - no layout
│   │   └── [page]/
│   │       ├── components/
│   │       │   ├── hooks/
│   │       │   │   └── use[Component].ts
│   │       │   └── [Component].tsx
│   │       ├── hooks/
│   │       │   └── use[Page].ts
│   │       ├── services/
│   │       │   └── [page].ts
│   │       └── page.tsx
│   ├── (dashboard)/         # Protected route group - sidebar layout
│   │   ├── hooks/
│   │   │   └── use[Layout].ts
│   │   ├── layout.tsx
│   │   └── [section]/
│   │       ├── components/
│   │       │   ├── hooks/
│   │       │   │   └── use[Component].ts
│   │       │   └── [Component].tsx
│   │       ├── hooks/
│   │       │   └── use[Page].ts(x)
│   │       ├── services/
│   │       │   └── [section].ts
│   │       ├── page.tsx             # List
│   │       ├── new/page.tsx         # Create
│   │       └── [id]/page.tsx        # Detail
│   ├── layout.tsx
│   └── page.tsx
├── components/
│   ├── ui/                  # shadcn/ui primitives
│   └── [domain]/            # Domain-specific components
├── constants/
│   ├── app.ts               # App-level constants (APP_NAME, MERCURE_URL, etc.)
│   └── [domain].ts          # Domain-specific constants (e.g. operators.ts)
├── hooks/                   # Reusable hooks (client)
├── i18n/
│   ├── en.json              # English translations
│   └── fr.json              # French translations (add more locales here)
├── lib/
│   ├── api.ts               # Fetch wrapper - JWT injection, 401 redirect
│   ├── i18n.ts              # next-intl config: routing, navigation, getRequestConfig
│   └── utils.ts             # Pure helpers (cn, formatAmount…) - NO utils/ subfolder
├── schemas/                 # Zod schemas + inferred form types
├── services/                # Reusable services (server)
├── store/                   # Zustand stores (useUIStore…)
└── types/
    └── [domain].ts          # One file per domain (e.g. auth.ts, operator.ts, transaction.ts)
```

## Backend `src/` Layout

```
src/
├── Entity/       - Doctrine entities
├── Repository/   - All queries (never in services)
├── Service/      - Business logic (all classes named *Service)
└── ...
```

## Component & Hook Placement

**Hooks** are the logic layer for **Client Components**. **Services** are the equivalent for **Server Components** - they fetch and shape data server-side, never run in the browser.

| Scope                                       | Location                                              |
| ------------------------------------------- | ----------------------------------------------------- |
| Reusable component (multiple pages)         | `src/components/[domain]/[Component].tsx`             |
| Component specific to one page              | `src/app/.../components/[Component].tsx`              |
| Reusable hook (multiple pages)              | `src/hooks/use[Domain].ts`                            |
| Hook specific to one page                   | `src/app/.../hooks/use[PageName].ts`                  |
| Hook specific to a reusable component       | `src/components/[domain]/hooks/use[ComponentName].ts` |
| Hook specific to a colocated page component | `src/app/.../components/hooks/use[ComponentName].ts`  |
| Reusable service (multiple pages)           | `src/services/[domain].ts`                            |
| Service specific to one page               | `src/app/.../services/[section].ts`                   |

## Scripts That Must Run Before First Paint

For the rare case where something has to happen before the browser paints
(e.g. a dark-mode class on `<html>` to avoid a flash of the wrong theme, or
scrolling to an anchor on a direct link) - a regular Client Component's
`useEffect` is too late: it only runs after React hydrates, well after first
paint. Use `next/script` with `strategy="beforeInteractive"` in the root
`layout.tsx` instead - it injects and blocks like a classic `<script>`, ahead
of hydration. Reserve it for this specific problem; everything else that can
afford to run after first paint stays a normal Client Component/hook.

## Key Invariants

- The `<main>` landmark lives **only** in route group layouts - never in individual pages or client components.
- The backend defines all API routes - frontend never invents routes.
- All entity IDs are UUIDs (strings) - never cast to number.
- Auth guard lives in `src/proxy.ts` (Clerk) - never implement a custom auth guard.

## System Boundaries

- `backend/` - owns all data, business rules, and API responses.
- `frontend/` - owns all UI; consumes the API; never accesses the DB directly.
- `infra/` - owns deployment scripts and nginx config; no application logic.

## Storage Model

- **PostgreSQL**: all persistent data via Doctrine entities.
- **Money**: stored as integers (cents) - never floats.
- **Timestamps**: `createdAt` / `updatedAt` on all entities via lifecycle callbacks.

## Auth and Access Model

- **Clerk** handles auth, JWT, and OAuth - never implement custom auth flows.
- Auth guard in `src/proxy.ts` protects the `(dashboard)` route group via `clerkMiddleware`.
- [Define ownership and access rules here once known for the project.]
- **Every user-owned resource MUST be listed in `CurrentUserExtension::OWNED_RESOURCES` and MUST throw `AccessDeniedException` when no authenticated user is present — never `return` silently.** A silent return exposes all rows if a route is ever made public. See `coding-conventions/symfony.md` → _Data isolation — CurrentUserExtension_.

## Project-Specific Invariants

1. [Add invariants here as the project evolves.]
