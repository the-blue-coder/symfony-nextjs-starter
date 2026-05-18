# TypeScript / React

### Control structures - always use braces

Never one-liner `if`/`else`/`for`/`while`. Always use braces, even for single statements.

```ts
// ❌ wrong
if (!user) return null;

// ✅ correct
if (!user) {
    return null;
}
```

### Package manager

Use **pnpm** for all frontend operations - never npm or yarn.

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

### ⚠️ File structure - ORDER IS STRICT

The `const Component` ALWAYS comes immediately after imports. Types ALWAYS go at the BOTTOM, JUST BEFORE `export default`.

The ONLY things allowed **above** `const Component`:
1. `"use client"` directive (client components only)
2. Imports
3. Next.js framework exports on server components: `metadata`, `generateMetadata`, `generateStaticParams`, `revalidate`, `dynamic` - nothing else

```tsx
"use client";

import { ... } from "...";

const MyComponent: React.FC<TMyComponentProps> = ({ ... }) => {
    const { ... } = useMyComponent();
    // useEffect + return
};

// helpers / supporting consts - BELOW the component

type TMyComponentProps = { ... };

export default MyComponent;
```

**Checklist:**
- [ ] Nothing between imports and `const Component` except allowed exceptions.
- [ ] All `type T...` declarations BELOW the component, JUST BEFORE `export default`.
- [ ] Helper consts/functions BELOW the component.
- [ ] `export default Component;` is the last line.

### Prop type naming - STRICT

Component prop types are **always** named `T[ComponentName]Props`.

```tsx
// ❌ wrong
type Props = { ... };
type MyComponentProps = { ... };
type TProps = { ... };

// ✅ correct
type TMyComponentProps = { ... };
```

### Variables-first, functions-last - alphabetical within each group

1. Variables before functions.
2. Alphabetical within each group (A → Z).

```ts
// ✅ correct
return { derived, isLoading, value, handleSubmit, setValue };

// ❌ wrong - not alphabetical
return { value, isLoading, derived, setValue, handleSubmit };

// ❌ wrong - functions mixed with variables
return { setValue, value, handleSubmit, isLoading };
```

- **Variables** (first, A → Z): state, derived values, refs, router, props.
- **Functions** (last, A → Z): setters (`setX`), handlers (`handleX`, `onX`).

### Style rules

- **Max 120 lines per component** - beyond that, split into sub-components.
- Arrow functions everywhere - `React.FC`, `const useX = () => ...`, `export const fn = () => ...`. Never `function` declarations in `src/lib/`.
- With props: `const Foo: React.FC<TFooProps> = ({ ... }) => { ... }`
- Without props: `const Foo: React.FC = () => { ... }` - `React.FC` is mandatory even with no props.
- **`export default`** for all principal exports - last line of file.
- Barrel files: `export { default as Foo } from "./Foo"`.
- Use `type` - never `interface`.
- All TypeScript types prefixed with `T`: `type TFooProps`, `type TVariant`.
- All clickable elements must have `cursor-pointer`.
- **Never put logic directly in JSX event attributes** - extract to a named handler: `onClick={handleClick}`, never `onClick={() => doX()}`.
- **Environment variables**: never read `process.env.NEXT_PUBLIC_*` directly in components. Extract to `src/constants/app.ts`.
- **Pluralization**: `{count} {count === 1 ? "item" : "items"}` - never hardcode the plural form.
- `new Date()` for display → use **Moment.js** instead.

### File and folder structure

- Pure helpers: `src/lib/utils.ts` - no `utils/` subfolder.
- Domain types: one file per domain in `src/types/` (`auth.ts`, `order.ts`…) - never a catch-all `index.ts`.
- App-wide constants: `src/constants/app.ts`. Domain constants in their own file.
- Config values (locales, etc.): `src/i18n/routing.ts` - i18n config lives in `src/i18n/`.

---

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

---

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

---

## Testing

- **Jest + React Testing Library**. Tests colocated with components (`*.test.tsx`).
- Unit tests for **hooks**, **utils**, and components with non-trivial logic.
- ✅ Test: complex hooks, critical business logic. ❌ Skip: UI components without logic, config files.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| Put `const`/handler in component body | Move to `use[ComponentName]` |
| `onClick={() => doX()}` inline | Named handler in hook → `onClick={handleClick}` |
| `useState` for API data | TanStack Query (client) or native `fetch` (SSR) |
| `useContext` for auth/UI state | Zustand store |
| `setIsLoading(false)` after form success + navigation | Keep disabled; use `isNavigating` combined with `isSubmitting` |
| `finally { setIsLoading(false) }` on navigating form | Never - let RHF reset `isSubmitting` |
| `process.env.NEXT_PUBLIC_*` in a component | `src/constants/app.ts` |
| Pure helper in `lib/utils/` | `src/lib/utils.ts` - no `utils/` subfolder |
| Domain types in `lib/types.ts` | One file per domain in `src/types/` |
| App-wide constants in `lib/constants.ts` | `src/constants/app.ts` |
| `interface Foo` | `type TFoo` |
| `type Props` / `type MyComponentProps` | `type TMyComponentProps` |
| `type T...` above the component | BOTTOM of file, JUST BEFORE `export default` |
| Helper const/function above the component | Move BELOW the component |
| Named export for a component | `export default` (last line) |
| Component > 120 lines | Split into sub-components |
| Return `{ setX, value }` | Variables-first A→Z, functions-last A→Z → `{ value, setX }` |
| `{count} items` hardcoded plural | `{count} {count === 1 ? "item" : "items"}` |
| `new Date()` for display | Moment.js |
| `if (!x) return;` one-liner | Always braces: `if (!x) { return; }` |
