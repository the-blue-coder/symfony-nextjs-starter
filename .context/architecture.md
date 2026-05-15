# Architecture

## Stack

| Layer          | Technology                        | Role                              |
| -------------- | --------------------------------- | --------------------------------- |
| Backend        | Symfony 7 + API Platform 3        | REST API (JSON-LD)                |
| Database       | PostgreSQL + Doctrine ORM         | Persistence                       |
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
├── backend/     - Symfony + API Platform (Docker)
├── frontend/    - Next.js 16 + pnpm (runs locally)
├── infra/       - deploy script + nginx configs
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
│   ├── routing.ts           # defineRouting - locales, defaultLocale
│   ├── request.ts           # getRequestConfig - server-side locale + message loading
│   └── navigation.ts        # createNavigation - locale-aware Link, usePathname, useRouter, redirect
├── lib/
│   ├── api.ts               # Fetch wrapper - JWT injection, 401 redirect
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

## Key Invariants

- The `<main>` landmark lives **only** in route group layouts - never in individual pages or client components.
- The backend defines all API routes - frontend never invents routes.
- All entity IDs are UUIDs (strings) - never cast to number.
- Auth guard lives in `src/middleware.ts` (Clerk) - never implement a custom auth guard.

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
- Auth guard in `src/middleware.ts` protects the `(dashboard)` route group via `clerkMiddleware`.
- [Define ownership and access rules here once known for the project.]

## Project-Specific Invariants

1. [Add invariants here as the project evolves.]
