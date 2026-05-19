# Next.js

### Server vs Client components - default to server

RSC flips the React model: **the server does the heavy work, the client handles interactivity only.**

- **Fetch data on the server.** Never fetch in a `useEffect` what you can `await` in a Server Component.
- **Keep client components small and leaf-level.** A `"use client"` boundary wraps only the interactive part.
- **Never leak server logic into the client.** DB queries, secret env vars, heavy business logic - server only.
- **Avoid unnecessary state.** If a value can be derived from server data or URL params, no `useState` needed.
- **Stream UI instead of blocking.** Use `<Suspense>` with `loading.tsx` or inline fallbacks.

**Default rule**: no interactivity → server. Needs interaction → `"use client"`.

Never add `"use client"` preemptively. Start server-side; only opt into client when required.

**Checklist - before adding `"use client"`:**

- [ ] Uses `useState`, `useEffect`, `useRef`, event handlers, or browser APIs? If no → stays on server.
- [ ] Can data fetching move to the nearest Server Component parent? If yes → do it.
- [ ] Is the boundary as narrow as possible? If no → extract the interactive part.
- [ ] Secret env vars or DB access visible? If yes → move to server.

### Authentication

- Auth guard lives in `src/middleware.ts` - Clerk middleware protects routes and handles redirects.
- **Clerk** handles auth, JWT, and OAuth providers - never implement custom auth flows.

### Loading gates - prevent content flash

**Screens must never render content until all async dependencies are fully resolved.** Rendering with partial/empty data causes a visible flash followed by a loading state, which is worse than showing a spinner from the start.

**The Clerk trap**: `isSignedIn` starts as `undefined` while Clerk initialises. Queries guarded by `enabled: !!isSignedIn` will have `isLoading: false` during that window (the query hasn't started yet), so `isLoading` is deceptively false and the screen renders empty.

**Rule**: every authenticated screen must include `!isLoaded` (Clerk) in its loading gate.

```ts
const { getToken, isLoaded, isSignedIn } = useAuth();

const { isLoading: isLoadingA } = useQuery({ enabled: !!isSignedIn, ... });
const { isLoading: isLoadingB } = useQuery({ enabled: !!isSignedIn, ... });

// Correct - Clerk + all queries
const isLoading = !isLoaded || isLoadingA || isLoadingB;
```

```tsx
// Component gate - one check, no partial renders
if (isLoading) return <LoadingSpinner />;
return <ActualContent />;
```

- Never render a page or card with empty/default data while real data is in flight.
- A single top-level `if (isLoading)` guard is enough - no need for skeleton states in each sub-component.

### i18n - file locations

| File / dir | Purpose |
| --- | --- |
| `src/i18n/routing.ts` | `defineRouting` - locales list, defaultLocale |
| `src/i18n/request.ts` | `getRequestConfig` - server-side locale + message loading |
| `src/i18n/navigation.ts` | `createNavigation` - exports locale-aware `Link`, `usePathname`, `useRouter`, `redirect` |
| `messages/` | Translation files at project root - one JSON per locale: `en.json`, `fr.json` |

- `next.config.ts` points the next-intl plugin at `./src/i18n/request.ts`.
- Dynamic import path: `` `../../messages/${locale}.json` `` (from `src/i18n/request.ts`).
- Never put message files in `public/` or `src/`.
- Always import `Link`, `usePathname`, `useRouter`, `redirect` from `@/i18n/navigation` - never from `next/link` or `next/navigation` in components that need locale awareness.

### Third-party libraries

| Library | Role |
|---|---|
| **BProgress** | Progress bar on route transitions; wrap in a client component, mount in root layout. |
| **next-intl** | i18n - all user-facing strings must go through it, never hardcode. |
| **react-cookie-consent** | Cookie consent - `CookieConsentBanner` in `src/components/layout/`; mount once in root layout. |
| **Clerk** | Auth (sessions, JWT, OAuth) - never implement custom auth flows. |
| **Moment.js** | Date/time formatting - never use raw `Date` methods for display or arithmetic. |

### Theme flash prevention (dark/light mode)

Inject a **blocking inline** `<script>` in `src/app/layout.tsx` inside `<head>`, **before any stylesheet**:

```tsx
<head>
    <script
        dangerouslySetInnerHTML={{
            __html: `
                (function() {
                    try {
                        var t = localStorage.getItem('theme');
                        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                            document.documentElement.classList.add('dark');
                        }
                    } catch(e) {}
                })();
            `,
        }}
    />
</head>
```

- Script must be **inline and blocking** - never `async` or `defer`.
- First child of `<head>`, before any `<link>` or `<style>`.
- Wrap in `try/catch` - `localStorage` can throw in private browsing.
- Zustand `useUIStore` theme state must initialize from `localStorage` on mount (in a `useEffect`), never from SSR.
- Tailwind: `darkMode: 'class'` (or `@variant dark` with `.dark` in Tailwind v4).
- **Never** read `document` or `localStorage` at module level - SSR will throw.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| TanStack Query in a Server Component | Native `fetch` |
| Hardcode a user-facing string | Route through `next-intl` |
| Config values (locales…) in `lib/` | `src/i18n/routing.ts` |
| Random constant above a server component | Only Next.js framework exports allowed above the component |
