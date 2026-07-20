---
description: "Implement a change directly from a free-form request, skipping /spec - then automatically run convention and security review before handoff"
argument-hint: "<description of what to build/fix>"
---

Implement a change end-to-end from a plain-language request, without creating or touching a spec in `.context/feature-specs/`. Always finishes with an automatic convention review and security review, same as `/implement` does for spec-based work.

Request: `$ARGS`

If `$ARGS` is empty, ask: **What do you want me to build or fix?** and wait for the answer.

---

## Step 1 - Load project context (silent)

Read:
- `.context/ai-workflow-entrypoint.md`
- `.context/project-overview.md`
- `.context/architecture.md`
- `.context/coding-conventions/global.md`
- `.context/coding-conventions/security.md`

---

## Step 2 - Scope the request

Infer scope from the request and the codebase: `frontend`, `backend`, or `all`.

Load layer-specific context (silent):
- Touches `frontend/` -> read `.context/coding-conventions/typescript.md`, `.context/coding-conventions/nextjs.md`, `.context/coding-conventions/tailwind.md`, `.context/coding-conventions/ui.md`, and `.context/ui-context.md`
- Touches `backend/` -> read `.context/coding-conventions/php.md` and `.context/coding-conventions/symfony.md`
- Touches infra / env vars -> read `.context/infra.md`

Explore the codebase silently: find the closest existing analog (entity, repo, service, page, hook) and read it. Identify which files will be created vs. modified.

If the request is ambiguous or clearly spans multiple sessions of work (i.e. it is really a feature, not a quick change), stop and tell the user: **This looks bigger than a quick change - run `/spec` first so it's properly planned, then `/implement`.** Wait for confirmation before proceeding either way.

---

## Step 2.5 - Delegate implementation to a specialized subagent

If you were spawned by another command to execute only a subset of these steps, skip this delegation and go straight to Step 3.

Otherwise, launch a subagent specialized for implementation work (agent type: `implementer`, if your tool supports named subagent types - otherwise a general coding subagent). Since it starts with a fresh context, instruct it to first read `.context/project-overview.md`, `.context/architecture.md`, `.context/coding-conventions/global.md`, and `.context/coding-conventions/security.md`, then execute Steps 3 through 5 below for the request: `<the request text>`. Wait for its report (files created/modified, decisions made, open questions), then continue to Step 6.

---

## Step 3 - Implement

**TDD checkpoint - mandatory for critical business logic and bug fixes, no exceptions:** before writing a single line of implementation/fix code for a service, domain logic, or regression, write the failing test first, run it, and confirm it fails for the right reason. State this explicitly (e.g. "Red: wrote failing test for X, confirmed failing") before moving on. Skipping this step is a process violation - see `.context/ai-workflow-rules.md`. Simple CRUD, UI components, and config keep the existing test-after convention.

Work in dependency order where applicable: **backend entities -> repositories -> services -> migrations -> API -> frontend schemas -> hooks -> components -> pages**.

Follow all conventions from `.context/coding-conventions/` strictly. Do not invent requirements beyond the request - if something is genuinely unclear, ask rather than guess.

---

## Step 4 - Verify

Quick sanity check:
- **Backend**: no PHP syntax issues, no inline FQN (`new \Foo()` -> use `use`), no EntityManager queries in services, no `*Client`/`*Manager` class names in `src/Service/`
- **Frontend**: TypeScript consistent, no hardcoded strings, no swallowed errors, no `console.log`, hook/component split respected, file order correct

---

## Step 5 - Update CHANGELOG.md

Add one bullet under `## [Unreleased]` (create it if missing) following Keep a Changelog format (`Added` / `Changed` / `Fixed`). Describe the user-facing outcome, not the files touched.

---

## Step 6 - Automatic convention review

Launch a subagent (agent type: `convention-reviewer` if supported, otherwise a general coding subagent) with this prompt:

> Read `.context/commands/review-changes.md` and follow its instructions (scope: `<frontend|backend|all>`, inferred in Step 2) to check and fix convention violations in the files changed by this request. Report back the summary table.

---

## Step 7 - Automatic security review

Launch a subagent (agent type: `security-reviewer` if supported, otherwise a general coding subagent) with this prompt:

> Read `.context/commands/review-security.md` and execute Steps 1 through 5 (load security conventions, collect changed files, analyze and fix violations) for the files changed by this request. Do NOT do Step 6 (manual check reminder) - the caller handles that. Report back the summary table.

---

## Step 8 - Hand off

Tell the user:
- What was implemented (files created/modified) and any decisions made or open questions.
- The convention-review and security-review summary tables.
- > Before committing, do a quick manual scan of the diff (`git diff HEAD`) to catch anything automated review may have missed - this is not a substitute for a real pentest on anything handling money, auth, or PII.
- Once satisfied, run `/commit-and-push`.

---

## Rules

- Never create or modify anything in `.context/feature-specs/` or `.context/progress-tracker.md` - this command is for changes too small to warrant a spec.
- The user manages Git. Never commit or push, even after a clean review - always hand off to the user first.
- Follow all conventions from `.context/coding-conventions/`. When in doubt, re-read them.
- If in doubt about whether the request is "quick" enough, ask the user rather than assume - see Step 2.
