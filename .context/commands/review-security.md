---
description: "Complete a security review of the pending local changes - checks against this project's own security conventions plus a generic OWASP-style checklist, and fixes violations in-place"
argument-hint: ""
---

You are a strict security reviewer. Your job is to collect every locally modified or new file, check them against this project's security conventions and a generic security checklist, and fix all violations in-place.

## Step 0.5 - Delegate the review to a specialized subagent

If you were spawned by another command to execute only a subset of these steps, skip this delegation and go straight to Step 1.

Otherwise, launch a subagent specialized for security review (agent type: `security-reviewer`, if your tool supports named subagent types - otherwise a general coding subagent) to execute Steps 1 through 5 below. Wait for its summary table, then continue to Step 6.

---

## Step 1 - Load security conventions

Read, in this order:

1. `.context/coding-conventions/security.md` - **authoritative for this project**. Trust boundaries, auth, webhooks, secrets, CORS, rate limiting, error responses - all specific to this Symfony + Next.js stack.
2. `.claude/skills/security-review-ecc/SKILL.md` - a **generic, supplementary** OWASP-style checklist (secrets, input validation, SQL injection, XSS, CSRF, rate limiting, sensitive data exposure, dependency security). Use it to catch anything not already covered by `security.md`, and translate its TypeScript/Next.js-flavored examples to this project's actual stack (PHP/Symfony on the backend, Next.js on the frontend) - never apply it literally where it conflicts with `security.md`.

Where the two disagree, `security.md` wins - it reflects decisions already made for this codebase.

## Step 2 - Collect changed files

Run all three commands and union the results:

```bash
git diff HEAD --name-only          # tracked, unstaged changes
git diff --cached --name-only      # tracked, staged changes
git ls-files --others --exclude-standard  # untracked (new) files
```

If there are no files at all, report "No changes to review" and stop.

## Step 3 - Analyze violations

For each changed file, read it in full and check it against every rule loaded in Step 1. Do not maintain a separate checklist here that duplicates their content - re-open the relevant file rather than trusting a paraphrase that can drift out of sync.

Focus areas per `security.md`'s own structure: trust boundaries, JWT/auth handling, data isolation (`CurrentUserExtension`), webhook signature verification, secrets/env vars, CORS, error responses, rate limiting, transport/HTTP headers, frontend route guards, dependencies.

## Step 4 - Fix violations

For each violation found:
1. State clearly: **file**, **line(s)**, **rule violated** (and its source - `security.md` or the generic checklist), **what you're changing**.
2. Apply the fix.
3. Do not refactor unrelated code. Touch only what violates a security rule.

## Step 5 - Summary

After all fixes, output a concise table:

| File | Violations found | Fixed |
|------|-----------------|-------|
| ...  | ...             | ✅/❌  |

If nothing was wrong, say so explicitly.

---

## Step 6 - Manual check reminder

Tell the user:
> Before committing, do a quick manual scan of the diff (`git diff HEAD`) to catch anything automated review may have missed - this is not a substitute for a real pentest on anything handling money, auth, or PII.
