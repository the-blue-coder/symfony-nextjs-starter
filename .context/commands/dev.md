---
description: "Implement a feature from its spec in .context/feature-specs/"
argument-hint: "<spec number or name fragment>"
---

Pick up and implement a feature from its spec.

Spec to work on (optional - skip to show the menu): `$ARGS`

---

## Step 1 - Load project context (silent)

Read:
- `.context/ai-workflow-entrypoint.md`
- `.context/project-overview.md`
- `.context/architecture.md`
- `.context/coding-conventions/global.md`

---

## Step 2 - Find specs

List all files in `.context/feature-specs/` and read the `status:` frontmatter field from each.

**If the directory is empty or no files have `status: todo` or `status: in-progress`:**
> No specs to implement. Run `/plan` first to define a feature, then come back.
Stop.

**If `$ARGS` is provided**, find the matching spec (by numeric prefix or name fragment, case-insensitive) and jump to Step 4.

**Otherwise**, display the menu in two sections:

```
▶ In progress
  1. 002 - Feature name

◦ Todo
  2. 001 - Feature name
  3. 003 - Feature name
```

Ask: **Which feature do you want to implement? (enter a number)**
Wait for the answer.

---

## Step 3 - Show the spec summary

Read the chosen spec file. Display:
- Title and goal
- Acceptance criteria (checklist)
- Scope: Frontend / Backend / Full-stack (inferred from the spec)

Ask: **Ready to start? (yes / no)**
Wait for confirmation.

---

## Step 4 - Mark in progress

Update the spec file's frontmatter: `status: todo` → `status: in-progress`.

Also update `.context/progress-tracker.md`: move the feature from **Next Up** to **In Progress** if it isn't already there.

---

## Step 5 - Load layer-specific context (silent)

Based on the spec's scope:
- Touches `frontend/` → read `.context/coding-conventions/typescript.md`, `.context/coding-conventions/nextjs.md`, `.context/coding-conventions/tailwind.md`, `.context/coding-conventions/ui.md`, and `.context/ui-context.md`
- Touches `backend/` → read `.context/coding-conventions/php.md` and `.context/coding-conventions/symfony.md`
- Touches infra / env vars → read `.context/infra.md`

Then explore the codebase silently:
- Find the closest existing analog feature (entity, repo, service, page, hook) and read it.
- Identify which files will be created vs. modified.

---

## Step 6 - Implement

The spec's **API Contract** table is a strict contract - implement exactly what's specified: method, route, request body fields (names, types, constraints), success status code, response shape, error responses, and auth. Do not add, remove, or rename fields.

Work through the spec systematically, in dependency order:
**backend entities → repositories → services → migrations → API → frontend schemas → hooks → components → pages**

For each unit of work:
- Follow all conventions from `.context/coding-conventions/` strictly.
- Run required commands (migrations, `pnpm install`, etc.) as needed.
- After completing each acceptance criterion, check it off in the spec file:
  `- [ ] criterion` → `- [x] criterion`

Do not cut corners. Implement completely and correctly before moving on.

---

## Step 7 - Verify

Quick sanity check:
- **Backend**: no PHP syntax issues, no inline FQN (`new \Foo()` → use `use`), no EntityManager queries in services, no `*Client`/`*Manager` class names in `src/Service/`
- **Frontend**: TypeScript consistent, no hardcoded strings, no swallowed errors, no `console.log`, hook/component split respected, file order correct

---

## Step 8 - Update CHANGELOG.md

Add one bullet under `## [Unreleased]` (create it if missing) following Keep a Changelog format (`Added` / `Changed` / `Fixed`). Describe the user-facing outcome, not the files touched.

---

## Step 9 - Hand off

Do NOT mark the spec as done yet - that's `/review-spec-implementation`'s job.

Tell the user:
- What was implemented (files created/modified).
- Any deviations from the spec, and why.
- Whether there are open questions left in the spec.

Then:
> Run `/review-spec-implementation` to check every acceptance criterion, data model, and API contract against the code before marking this spec done.
> If context is getting long, start a fresh session before running `/review-spec-implementation`.

---

## Rules

- Never mark a spec done before all acceptance criteria are checked off.
- Never invent behavior not described in the spec - add open questions instead.
- The user manages Git. Never commit unless explicitly asked.
- Follow all conventions from `.context/coding-conventions/`. When in doubt, re-read them.
