---
description: "Review all local changes (tracked + untracked) against project conventions and fix violations. Args: frontend | backend | all"
argument-hint: "frontend | backend | all"
---

You are a strict code reviewer. Your job is to collect every locally modified or new file in the declared scope, check them against the project's conventions, and fix all violations in-place.

## Scope

Argument: `$ARGS`

- `frontend` → only files inside `frontend/`
- `backend` → only files inside `backend/`
- `all` → files in both `frontend/` and `backend/`
- **Never touch** `infra/`, `.claude/`, `.github/`, or any file outside the declared scope.

## Step 1 - Load conventions

Read the convention files that apply to the declared scope:

- Always read `.context/coding-conventions/global.md` (cross-cutting rules)
- If scope includes `frontend` → also read `.context/coding-conventions/typescript.md`, `.context/coding-conventions/nextjs.md`, `.context/coding-conventions/tailwind.md`, `.context/coding-conventions/ui.md`
- If scope includes `backend` → also read `.context/coding-conventions/php.md`, `.context/coding-conventions/symfony.md`

## Step 1.5 - Delegate the review to a specialized subagent

If you were spawned by another command to execute only a subset of these steps, skip this delegation and go straight to Step 2.

Otherwise, launch a subagent specialized for convention review (agent type: `convention-reviewer`, if your tool supports named subagent types - otherwise a general coding subagent) to execute Steps 2 through 5 below with the declared scope. Wait for its summary table, then continue to Step 6.

---

## Step 2 - Collect changed files

Run all three commands and union the results:

```bash
git diff HEAD --name-only          # tracked, unstaged changes
git diff --cached --name-only      # tracked, staged changes
git ls-files --others --exclude-standard  # untracked (new) files
```

Filter to only files inside the declared scope folders.

If there are no files at all in scope, report "No changes in scope" and stop.

## Step 3 - Analyze violations

For each changed file in scope, read the full file and check it against the loaded conventions.

**Frontend checklist (if scope includes frontend):**
- Hook/Component split: every component with state/handlers MUST call `use[ComponentName]`. Zero consts/handlers in component body.
- Every `useEffect` MUST have a `//` comment above explaining its intent.
- File order: component first, then types at the bottom just before `export default`. No types/consts/helpers above the component (except Next.js framework exports on server components).
- Prop types named `T[ComponentName]Props` - never `Props`, `TProps`, or unnamed.
- Control structures always use braces - no one-liner `if`/`else`/`for`.
- `interface Foo` → use `type TFoo` instead.
- No `useState` for API data (use TanStack Query).
- No `useContext` for auth/UI state (use Zustand).
- Dynamic plural nouns: always ternary - `{count === 1 ? "item" : "items"}`.
- No hardcoded app name - use `APP_NAME` constant from `src/lib/constants.ts`.
- Entity IDs stay as strings (UUIDs) - never `Number(id)`.
- `setIsLoading(false)` after form success+navigation → forbidden. Use `isNavigating`.
- No `finally` to reset loading on navigating forms.
- Helper consts/functions above the component → move below it.
- Named export for a component → use `export default`.
- Unused imports → remove all unused `import` statements.

**Backend checklist (if scope includes backend):**
- Never `$this->em->getRepository(Foo::class)` - inject repository via constructor.
- All queries in repositories, not services.
- Every class in `src/Service/` must end with `Service` - never `*Client`, `*Manager`, `*Handler`.
- Always `use DateTimeImmutable;` at top - never `new \DateTimeImmutable()` inline.
- Control structures always use braces - no one-liner `if`/`else`/`for`.
- Errors return `{ "message": "..." }` - never `error` or `detail`.
- Unused imports → remove all unused `use` statements.

**Cross-cutting (always):**
- No hardcoded env variables - always use `.env`.
- All code, comments, file contents in English.

## Step 4 - Fix violations

For each violation found:
1. State clearly: **file**, **line(s)**, **rule violated**, **what you're changing**.
2. Apply the fix.
3. Do not refactor unrelated code. Touch only what violates the rules.

## Step 5 - Summary

After all fixes, output a concise table:

| File | Violations found | Fixed |
|------|-----------------|-------|
| ...  | ...             | ✅/❌  |

If nothing was wrong, say so explicitly.

---

## Step 6 - Manual check reminder

Tell the user:
> Before committing, do a quick manual scan of the diff (`git diff HEAD`) to catch anything automated review may have missed - dead code, stray debug logs, TODO comments, or anything that looks off.
