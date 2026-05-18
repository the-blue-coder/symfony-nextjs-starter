# AI Workflow Rules

> Context loading order and file index: `.context/ai-workflow-entrypoint.md`.

## Approach

Build incrementally against the specs defined in `.context/`. Never infer or invent behavior not described there. When in doubt, resolve ambiguity in the relevant context file before writing code.

---

## Scoping Rules

- Work on one feature unit at a time.
- Small, verifiable increments over large speculative changes.
- Never combine unrelated system boundaries in a single step.

**Split a step if it touches:**

- UI changes AND background/async logic simultaneously
- Multiple unrelated API routes
- Behavior not defined in the context files

If a change cannot be verified end-to-end quickly, the scope is too broad - split it.

---

## Handling Missing Requirements

- Do not invent product behavior.
- If ambiguous, resolve it in the relevant `.context/` file first.
- If missing, add it as an open question in `progress-tracker.md` before continuing.

---

## Protected Files

Do not modify unless explicitly instructed:

- `src/components/ui/*` - shadcn/ui primitives (edit only to match design system)
- `src/middleware.ts` - auth guard (Clerk) + i18n routing
- `src/lib/api.ts` - fetch wrapper

---

## Keeping Docs in Sync

Update the relevant `.context/` file whenever implementation changes:

- System architecture, structure, or invariants → `architecture.md`
- Infrastructure, env vars, deploy config → `infra.md`
- Feature scope or status → `progress-tracker.md`
- Design tokens, layout, UI decisions → `ui-context.md`

---

## Before Moving to the Next Unit

1. The current unit works end-to-end within its defined scope.
2. No invariant defined in `architecture.md` was violated.
3. `progress-tracker.md` reflects the completed work.
4. `pnpm build` passes (frontend) and `php bin/phpunit` passes (backend).
5. `.env.example` is up to date if any env var was added.
6. `CHANGELOG.md` is updated.
