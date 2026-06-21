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

### Page titles - section-level metadata

**Every section must show `{Section} - {APP_NAME}` in the browser tab, never just `{APP_NAME}`.**

- The root layout sets a title **template**, not a static string: `` metadata.title = { default: APP_NAME, template: `%s - ${APP_NAME}` } ``. Never hardcode the app name as a plain string here - import `APP_NAME` from its constants file.
- Each top-level section under the dashboard route group (e.g. `dashboard/`, `settings/`, ...) that needs its own tab title gets a **thin Server Component `layout.tsx`** exporting `generateMetadata` - nothing else changes about how that section renders.
- **Reuse the existing sidebar nav translation key** for the title - never introduce a separate string. This keeps the tab title and the sidebar label in permanent sync and keeps the title localized for free.
- `generateMetadata` only works in a **Server Component**. If the section's layout currently does interactive work and is `"use client"`, split it: keep the interactive JSX in a sibling `*LayoutClient.tsx` client component, and reduce `layout.tsx` itself to a server wrapper that exports `generateMetadata` and renders `<XLayoutClient>{children}</XLayoutClient>`. This mirrors the existing `(dashboard)/layout.tsx` + `DashboardLayoutClient.tsx` split, one level deeper.
- Leaf pages inside a titled section need **no metadata of their own** - they inherit the section's title via the template.
- Transient pages with no real UI (e.g. a redirect-only page) do not need a section layout.

### Loading gates - prevent content flash

**Screens must never render content until all async dependencies are fully resolved.** Rendering with partial/empty data causes a visible flash followed by a loading state, which is worse than showing a spinner from the start.

---

> ## ⛔ NEVER USE `isLoading` ON A CONDITIONAL QUERY — USE `isPending`
>
> `isLoading = isPending && isFetching`. When a query is disabled (e.g. while `isSignedIn` is still `undefined`), `isFetching` is `false` → **`isLoading` is `false` even though there is no data yet** → your component sees an empty array and renders the empty state for one frame before the real data arrives.
>
> ```ts
> // ❌ WRONG — flashes empty state while query is disabled
> const { data, isLoading } = useQuery({ enabled: !!isSignedIn, ... })
>
> // ✅ CORRECT — stays pending until data is actually available
> const { data, isPending: isLoading } = useQuery({ enabled: !!isSignedIn, ... })
> ```
>
> **Every `useQuery` with an `enabled:` condition MUST alias `isPending` as `isLoading`. No exceptions.**

---

**The Clerk trap**: `isSignedIn` starts as `undefined` while Clerk initialises. This makes the `enabled:` flash bug above even more likely — always combine the `isPending` fix with an `!isLoaded` gate.

**Rule**: every authenticated screen must include `!isLoaded` (Clerk) in its loading gate.

```ts
const { getToken, isLoaded, isSignedIn } = useAuth();

const { isPending: isLoadingA } = useQuery({ enabled: !!isSignedIn, ... });
const { isPending: isLoadingB } = useQuery({ enabled: !!isSignedIn, ... });

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

### Cache invalidation - cross-domain query keys

---

> ## ⛔ A QUERY THAT EMBEDS DATA FROM ANOTHER DOMAIN GOES STALE WHEN THAT DOMAIN MUTATES — INVALIDATE BOTH
>
> TanStack Query caches each `queryKey` independently with a non-zero `staleTime` (see `useQueryProvider`). If endpoint A's response embeds a value that actually lives in domain B (a name, a preference, a derived total, a setting...), then mutating B through B's hook does **not** refresh A's cache - the user sees stale data until the TTL lapses or they hard-reload (F5).
>
> Typical shape this bug takes: a settings/profile mutation only invalidates its own `queryKey`, while some other aggregate/detail endpoint embeds one of the fields it just changed (a display preference, a denormalized name, a snapshot rate...). The user changes the setting, navigates back to the screen that embeds it, and sees the old value until F5.
>
> ```ts
> // ❌ WRONG — only invalidates this hook's own domain
> const updateMutation = useMutation({
>   mutationFn: updateProfile,
>   onSuccess: () => {
>     queryClient.invalidateQueries({ queryKey: QUERY_KEY }); // ["profile"]
>   },
> });
>
> // ✅ CORRECT — also invalidates every cache that embeds this domain's data
> const updateMutation = useMutation({
>   mutationFn: updateProfile,
>   onSuccess: () => {
>     queryClient.invalidateQueries({ queryKey: QUERY_KEY });
>     // <Aggregate> embeds profile-derived fields (e.g. display preferences, denormalized names) - it goes stale too
>     queryClient.invalidateQueries({ queryKey: AGGREGATE_QUERY_KEY_PREFIX }); // prefix match, all ids
>   },
> });
> ```
>
> **Before writing or reviewing ANY mutation's `onSuccess`, ask: "which OTHER endpoints' responses embed a field this mutation can change?" Trace it through the backend serializer/service, not just the frontend type — embedding is often invisible from the type alone (a service can reach into a related entity and inline its name/preference into a response without that relation showing up in the DTO). Invalidate every one of them. No exceptions.**

---

- Export a **prefix** alongside every parametrized `queryKey` factory (e.g. `AGGREGATE_QUERY_KEY_PREFIX = ["aggregate"]` next to `aggregateQueryKey = (id) => [...AGGREGATE_QUERY_KEY_PREFIX, id]`) so cross-domain invalidation can target *all* instances with `invalidateQueries({ queryKey: PREFIX })` (TanStack Query prefix-matches by default) without knowing every concrete id.
- This is **not** limited to settings/profile screens - it applies to renames of denormalized entity names embedded elsewhere, rate/price changes embedded in computed totals, and parent-record edits embedded in derived/aggregate views. Whenever you add a new field to a serialized response by reaching into another entity, you are creating a new cross-domain dependency - update the owning mutation's invalidation list in the same change.

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

### ⚠️ Hook/Component Split - THE MOST CRITICAL RULE

**Every component with logic/state MUST call `use[ComponentName]`.** No exceptions for complex components.

- **Hook** (`use[ComponentName]`) contains **ALL** of:
  - `useState`, `useRef`, `useRouter`, `usePathname`, `useCallback`, `useMemo`
  - All handlers (`handleX`, `onX`)
  - All derived `const` values (e.g. `const isActive = item.status === "active"`)
  - All local constants and computed values
- **Component** contains **ONLY**:
  - `useEffect` calls (stay in the component, NOT the hook)
  - The JSX `return`
- **Every `useEffect` MUST have a `//` comment on the line above** explaining its intent. A bare `useEffect` with no comment is a convention violation.
- **Exception**: simple wrapper components (no state, no handlers, no derived values) can return JSX directly.

**Checklist - before writing/reviewing any component:**

- [ ] Component has state, handlers, or derived values? → Must call `use[ComponentName]`.
- [ ] Component just wraps JSX with props? → OK to skip hook.
- [ ] If hook exists: ZERO `const`, `let`, handler definitions in the component body.
- [ ] Only `useEffect` calls between the hook call and `return`.
- [ ] Every `useEffect` has a `//` comment above it.

```tsx
// ❌ WRONG - consts and logic in the component body
const MyComponent: React.FC<TMyComponentProps> = ({ item }) => {
    const isActive = item.status === "active";
    const handleClick = () => doSomething();
    return <div onClick={handleClick}>{isActive ? "yes" : "no"}</div>;
};

// ✅ CORRECT - everything in the hook
const MyComponent: React.FC<TMyComponentProps> = ({ item }) => {
    const { isActive, handleClick } = useMyComponent({ item });
    return <div onClick={handleClick}>{isActive ? "yes" : "no"}</div>;
};
```

### Page-header-title hook call order

The page-header-title hook (e.g. `usePageHeaderTitle(t("title"), isPageLoading)`) must be called **immediately after** the component's main data/state hook destructuring - nothing in between.

```tsx
// ✅ correct
const { isPageLoading, stats, t, ... } = useDashboardStats();
usePageHeaderTitle(t("title"), isPageLoading);

// ❌ wrong - other code sits between the two hook calls
const { isPageLoading, stats, t, ... } = useDashboardStats();
useEffect(() => { ... }, []);
usePageHeaderTitle(t("title"), isPageLoading);
```

This keeps the title-setting call visually anchored to the `t`/`isPageLoading` values it depends on, regardless of how many `useEffect`s or early returns follow.

### Derived values belong in the hook, not the component

Any value derived from hook state (filtered lists, counts, booleans, formatted strings) must be computed inside the hook and returned — never derived inline in JSX or repeated across the component.

```ts
// ❌ wrong - derived inline in JSX, computed twice
{sidebarPeriods.filter(p => p.status === "sent").length > 0 && (
    <span>{sidebarPeriods.filter(p => p.status === "sent").length}</span>
)}

// ✅ correct - computed once in the hook, returned as a named value
const sentPeriodsCount = sidebarPeriods.filter(p => p.status === "sent").length;
return { ..., sentPeriodsCount };

// component just reads it
{sentPeriodsCount > 0 && <span>{sentPeriodsCount}</span>}
```

### Pure helper functions

Pure functions with no state or hook dependencies do NOT belong in a hook or a component file. Put them in `src/lib/utils.ts`.

- **Reusable across the app** → `src/lib/utils.ts` (exported).
- **Used only in one file** → still `src/lib/utils.ts` if it has no dependencies; moving it inline adds noise.
- **Never** define a stateless pure helper inside a hook body or at the bottom of a component file.

```ts
// ❌ wrong - pure helper inside a hook file
const useMyHook = () => { ... };
const formatDate = (d: string) => moment(d).format("MMM D"); // no state, no deps

// ✅ correct - in src/lib/utils.ts
export const formatDate = (d: string) => moment(d).format("MMM D");
```

## State Management

| What | Tool | Rule |
|---|---|---|
| Server state (client pages) | TanStack Query | Never use in Server Components |
| Server state (SSR pages) | Native `fetch` in Server Components | No TanStack Query here |
| Global client state | Zustand stores in `src/store/` | `useAuthStore`, `useUIStore` |
| Form state | React Hook Form + Zod (`src/schemas/`) | See Forms below |
| Local UI state | `useState` | Toggles/modals only - never for API data |

- **Never** use `useState` for data that comes from the API.
- **Never** use `useContext` for state that belongs in Zustand.

## Forms (React Hook Form + Zod)

- Schemas in `src/schemas/` - one file per entity.
- `useForm<T>({ resolver: zodResolver(schema), defaultValues: {...} })`.
- Edit pages: `reset()` inside `useCallback` to populate on load.
- Field arrays: `useFieldArray({ control, name: "..." })`.
- Spread `{...register("fieldName")}` on inputs.
- `error={errors.fieldName?.message}` for validation messages.
- Hooks return `{ submitError, errors, isSubmitting, register, handleSubmit }` - **never expose raw `form` object**.

**Loading state on form submit**

- On success: **never** call `setIsLoading(false)` - keep disabled until navigation completes.
- On error: set `setSubmitError(...)` in catch - RHF resets `isSubmitting` automatically.
- Use `isNavigating`: set `true` before `router.push()`, never reset.
- Combine: `disabled={isSubmitting || isNavigating}`.
- **Never use `finally`** to reset loading on forms that navigate on success.

### React component rules

- All clickable elements must have `cursor-pointer`.
- **Never put logic directly in JSX event attributes** - extract to a named handler: `onClick={handleClick}`, never `onClick={() => doX()}`.
- **Environment variables**: never read `process.env.NEXT_PUBLIC_*` directly in components. Extract to `src/constants/app.ts`.

### File and folder structure

- Pure helpers: `src/lib/utils.ts` - no `utils/` subfolder.
- Domain types: one file per domain in `src/types/` (`auth.ts`, `order.ts`…) - never a catch-all `index.ts`.
- App-wide constants: `src/constants/app.ts`. Domain constants in their own file.
- Config values (locales, etc.): `src/i18n/routing.ts` - i18n config lives in `src/i18n/`.

### Testing

- **Jest + React Testing Library**. Tests colocated with the file they cover, same directory (`*.test.ts(x)`).
- Unit tests for **hooks**, **utils**, and components with non-trivial logic.
- ✅ Test: complex hooks, critical business logic. ❌ Skip: UI components without logic, config files.
- **TDD (test-first) is MANDATORY** for critical business logic and bug fixes — write the failing test before the implementation/fix, no exceptions.

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
| Put `const`/handler in component body | Move to `use[ComponentName]` |
| `onClick={() => doX()}` inline | Named handler in hook → `onClick={handleClick}` |
| `useState` for API data | TanStack Query (client) or native `fetch` (SSR) |
| `useContext` for auth/UI state | Zustand store |
| `setIsLoading(false)` after form success + navigation | Keep disabled; use `isNavigating` combined with `isSubmitting` |
| `finally { setIsLoading(false) }` on navigating form | Never — let RHF reset `isSubmitting` |
| `process.env.NEXT_PUBLIC_*` in a component | `src/constants/app.ts` |
| Pure helper at the bottom of a hook/component file | `src/lib/utils.ts` |
| Domain types in a single `types.ts` | One file per domain in `src/types/` |
| App-wide constants scattered in hooks | `src/constants/app.ts` |
