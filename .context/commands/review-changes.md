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

## Step 0.5 - Delegate the review to a specialized subagent

If you were spawned by another command to execute only a subset of these steps, skip this delegation and go straight to Step 1.

Otherwise, launch a subagent specialized for convention review (agent type: `convention-reviewer`, if your tool supports named subagent types - otherwise a general coding subagent) to execute Steps 1 through 5 below with the declared scope. Wait for its summary table, then continue to Step 6.

---

## Step 1 - Load conventions

Read the convention files that apply to the declared scope:

- Always read `.context/coding-conventions/global.md` (cross-cutting rules)
- If scope includes `frontend` → also read `.context/coding-conventions/typescript.md`, `.context/coding-conventions/nextjs.md`, `.context/coding-conventions/tailwind.md`, `.context/coding-conventions/ui.md`
- If scope includes `backend` → also read `.context/coding-conventions/php.md`, `.context/coding-conventions/symfony.md`

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

For each changed file in scope, read the full file and check it against every rule in the convention files you loaded in Step 1 - read them in full, don't rely solely on their "Quick Reference" tables, which are abbreviated indexes, not complete rule sets. Do not maintain a separate checklist here that duplicates their content - if you need a reminder of what to check, re-open the relevant convention file rather than trusting a paraphrase that can silently drift out of sync with it.

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
