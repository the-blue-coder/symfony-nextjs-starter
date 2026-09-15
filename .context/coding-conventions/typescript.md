# TypeScript

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

### ⚠️ File structure - ORDER IS STRICT

This rule applies to **every** TypeScript file: components, hooks, stores, schemas, utilities — no exceptions.

The principal `const` (component, hook, store, …) ALWAYS comes immediately after imports. **All `type` declarations ALWAYS go at the BOTTOM, JUST BEFORE `export default`.**

The ONLY things allowed **above** the principal `const`:
1. `"use client"` directive (client files only)
2. Imports
3. Next.js framework exports on server components: `metadata`, `generateMetadata`, `generateStaticParams`, `revalidate`, `dynamic` - nothing else

The order of everything that follows the principal `const` is strict:

```
principal const   ← hook / component / store
─────────────────────────────────────────────
1. helper consts / exported consts  (e.g. export const QUERY_KEY = [...])
2. type declarations                (type TFoo = ...)
3. export default                   ← always last line
```

```tsx
// ✅ component
"use client";

import { ... } from "...";

const MyComponent: React.FC<TMyComponentProps> = ({ ... }) => {
    const { ... } = useMyComponent();
    // useEffect + return
};

// helpers / supporting consts - BELOW the principal const

type TMyComponentProps = { ... };

export default MyComponent;
```

```ts
// ✅ store
import { create } from "zustand";

const useMyStore = create<TMyStore>((set) => ({
    value: null,
    setValue: (v) => set({ value: v }),
}));

type TMyStore = {
    value: string | null;
    setValue: (v: string) => void;
};

export default useMyStore;
```

```ts
// ✅ hook
const useMyHook = () => {
    // ...
    return { value, handleSubmit };
};

export const MY_QUERY_KEY = ["my-entity"];

type TMyHookArgs = { id: string };

export default useMyHook;
```

**Checklist:**
- [ ] Nothing between imports and the principal `const` except allowed exceptions.
- [ ] All `type T...` declarations BELOW the principal `const`, JUST BEFORE `export default`.
- [ ] Helper consts / exported consts BELOW the principal `const`, BEFORE type declarations.
- [ ] `export default ...;` is the last line.

### Import grouping

Never split imports from the same module across multiple lines. Always merge them into a single `import` statement — applies to every module, not just `"react"`.

```ts
// ❌ wrong - same module imported twice
import { use } from "react";
import { useEffect } from "react";
import useFoo from "./hooks/useFoo";
import { TFooValues } from "./hooks/useFoo";

// ✅ correct - one line per module
import { use, useEffect } from "react";
import useFoo, { TFooValues } from "./hooks/useFoo";
```

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

- **Variables** (first, A → Z): state, derived values, refs, router, props, **and module-level `const` constants** (e.g. `NAV_ENTRIES`, `STATUS_LABELS`) - a constant is a variable, not a function.
- **Functions** (last, A → Z): setters (`setX`), handlers (`handleX`, `onX`), and any other exported function.

This applies just as much to a module's trailing `export { ... }` list as it does to a hook's return statement or a destructuring assignment - constants and other non-function exports go first (A → Z), functions go last (A → Z), never one flat alphabetical list mixing the two.

```ts
// ❌ wrong - one flat alphabetical list mixes constants and functions
export {
	ERROR_MESSAGES,
	formatLabel,
	MAX_ITEMS,
	parseResponse,
};

// ✅ correct - constants (variables) first, functions last, alphabetical within each group
export {
	ERROR_MESSAGES,
	MAX_ITEMS,
	formatLabel,
	parseResponse,
};
```

### Handlers - named consts in the hook body, never inline in the return

Every handler is its own named `const` in the hook's body, even a one-liner. The return statement only ever lists names - never defines a function inline.

```ts
// ❌ wrong - handler defined inline in the return object
return {
    title,
    handleTitleChange: (event: React.ChangeEvent<HTMLInputElement>) => setTitle(event.target.value),
};

// ✅ correct - named const above, return just lists it
const handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) => setTitle(event.target.value);

return { title, handleTitleChange };
```

This keeps the return statement a flat, scannable list of names (per the alphabetical rule above) and keeps every handler independently readable/testable, regardless of how trivial it looks.

### Style rules

- **Max 120 lines per component** - beyond that, split into sub-components.
- Arrow functions everywhere - `React.FC`, `const useX = () => ...`, `export const fn = () => ...`. Never `function` declarations in `src/lib/`.
- With props: `const Foo: React.FC<TFooProps> = ({ ... }) => { ... }`
- Without props: `const Foo: React.FC = () => { ... }` - `React.FC` is mandatory even with no props.
- **`export default`** for all principal exports - last line of file.
- Barrel files: `export { default as Foo } from "./Foo"`.
- Use `type` - never `interface`.
- All TypeScript types prefixed with `T`: `type TFooProps`, `type TVariant`.
- **Pluralization**: `{count} {count === 1 ? "item" : "items"}` - never hardcode the plural form.
- `new Date()` for display → use **Moment.js** instead.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| `interface Foo` | `type TFoo` |
| `type Props` / `type MyComponentProps` | `type TMyComponentProps` |
| `type T...` above the principal `const` (any file) | BOTTOM of file, JUST BEFORE `export default` |
| Helper const/function above the principal `const` | Move BELOW it — order: helpers → types → `export default` |
| `generateMetadata`/`metadata`/`dynamic`/`revalidate`/`generateStaticParams` placement | Above the principal `const`, alongside imports — Next.js's own special exports, not component logic |
| Same module imported twice | Merge into one `import` line |
| `function foo()` in `src/lib/` | Arrow function: `export const foo = () => ...` |
| Named export for a component | `export default` (last line) |
| Component > 120 lines | Split into sub-components |
| Return `{ setX, value }` | Variables-first A→Z, functions-last A→Z → `{ value, setX }` |
| `handleX: (e) => setX(e.target.value)` inline in the return | Named `const handleX = (e) => ...` in the hook body, return just `handleX` |
| `{count} items` hardcoded plural | `{count} {count === 1 ? "item" : "items"}` |
| `new Date()` for display | Moment.js |
| `if (!x) return;` one-liner | Always braces: `if (!x) { return; }` |
