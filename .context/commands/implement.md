---
description: "Implement a feature from its spec and verify it against the spec automatically (dev + review-spec-implementation loop)"
argument-hint: "<spec number or name fragment>"
---

Implement a feature end-to-end: run `/dev` in a subagent, then run `/review-spec-implementation` in a subagent against the result, and loop between the two until the spec is fully verified or a 5-iteration cap is hit. `/spec` (planning) and `/commit-and-push` stay independent - this command never plans a feature and never commits or pushes.

Spec to work on (optional - skip to show the menu): `$ARGS`

---

## Step 1 - Load project context and pick the spec (silent context load, interactive spec pick)

Read:
- `.context/ai-workflow-entrypoint.md`
- `.context/project-overview.md`
- `.context/architecture.md`

List all files in `.context/feature-specs/` and read the `status:` frontmatter field from each.

**If the directory is empty or no files have `status: todo` or `status: in-progress`:**
> No specs to implement. Run `/spec` first to define a feature, then come back.
Stop.

**If `$ARGS` is provided**, find the matching spec (by numeric prefix or name fragment, case-insensitive) and jump to Step 2.

**Otherwise**, display the menu (In progress / Todo sections, same format as `/dev`), ask **Which feature do you want to implement? (enter a number)**, and wait for the answer.

---

## Step 2 - Show the spec summary and confirm

Read the chosen spec file. Display title, goal, acceptance criteria checklist, and scope (Frontend / Backend / Full-stack).

Ask: **Ready to start? (yes / no)**
Wait for confirmation.

---

## Step 3 - Mark in progress

Update the spec file's frontmatter: `status: todo` → `status: in-progress`.
Update `.context/progress-tracker.md`: move the feature from **Next Up** to **In Progress** if it isn't already there.

---

## Step 4 - Dev/review loop (max 5 iterations)

Set `iteration = 1` and `pending_fixes = none`.

### 4a - Spawn the dev subagent

Launch a subagent (foreground - you need its result before reviewing) with this prompt:

> Read `.context/commands/dev.md`. Since you start with a fresh context, first read its Step 1 files yourself (`.context/ai-workflow-entrypoint.md`, `.context/project-overview.md`, `.context/architecture.md`, `.context/coding-conventions/global.md`, `.context/coding-conventions/security.md`), then execute Steps 5 through 8 (load layer-specific context, implement, verify, update CHANGELOG.md) for the spec at `<spec file path>`. Do not do Steps 1-4 (spec selection, confirmation, marking in-progress) - that has already been done.
> [If `pending_fixes` is not `none`:] The previous review pass found these gaps - fix them specifically, then re-run the Step 7 sanity check: `<pending_fixes>`.
> Report back: files created/modified, any deviations from the spec and why, and any open questions.

### 4b - Spawn the verification subagent

Launch a subagent (foreground - agent type: `spec-verifier` if your tool supports named subagent types, otherwise a general subagent) with this prompt:

> Read `.context/commands/review-spec-implementation.md` and execute Steps 1 through 6 (find the spec, load context, verify acceptance criteria, data model, and API contract) for the spec at `<spec file path>`. Do NOT do Step 7 (convention review), Step 8 (fix/flag), or Step 9 (commit/push) - the caller handles those. Return the structured report (verdicts + evidence) exactly as specified in Step 6.

### 4b bis - Spawn the convention-review subagent

Launch a subagent (foreground - agent type: `convention-reviewer` if your tool supports named subagent types, otherwise a general coding subagent) with this prompt:

> Read `.context/commands/review-changes.md` and follow its instructions (scope: whatever this spec touches - frontend, backend, or all) to check and fix convention violations in the files changed by this feature. Report back the summary table.

### 4b ter - Spawn the security-review subagent

Launch a subagent (foreground - agent type: `security-reviewer` if your tool supports named subagent types, otherwise a general coding subagent) with this prompt:

> Read `.context/commands/review-security.md` and execute Steps 1 through 5 (load security conventions, collect changed files, analyze and fix violations) for the files changed by this feature. Do NOT do Step 6 (manual check reminder) - the caller handles that. Report back the summary table.

### 4c - Evaluate the reports

- **If overall verdict is ✅ COMPLETE and no convention or security violations remain:** go to Step 5.
- **If any criterion/entity/route is ⚠️ or ❌, or convention or security violations remain:**
  - If `iteration == 5`: go to Step 6 (cap reached).
  - Otherwise: set `pending_fixes` to the concrete list of gaps and violations from all three reports, increment `iteration`, and go back to 4a.

---

## Step 5 - Mark done and hand off (success path)

- Check off any remaining `- [ ]` criteria in the spec.
- Update `status` to `done` in the spec and update `.context/progress-tracker.md` accordingly.
- Tell the user:
  > Spec fully verified, conventions clean, and security review passed after `<iteration>` iteration(s) - marking as done.
  > Before pushing, do a quick manual scan of the diff (`git diff HEAD`) to catch anything automated review may have missed - dead code, stray debug logs, TODO comments, or anything that looks off. Once satisfied, run `/commit-and-push`.

Stop.

---

## Step 6 - Cap reached (failure path)

Do NOT mark the spec as done. Do NOT commit or push anything.

Show the user the latest verification, convention-review, and security-review reports in full, then ask, per remaining gap: **Do you want me to fix this now, or log it as an open question in the spec?**
- Fix now → implement inline, then re-run only the relevant subagent to confirm (the convention-reviewer for a convention violation, the security-reviewer for a security violation, the spec-verifier for a specific criterion).
- Log it → add to the spec's **Open Questions** section: `- [ ] [criterion text] - not yet implemented after 5 dev/review iterations`.

Once resolved, update `status` in the spec (`done` only if every criterion ended up ✅; otherwise leave `in-progress` and rely on the logged open questions).

---

## Rules

- Never mark a spec done before all acceptance criteria are checked off and conventions are clean.
- Never invent behavior not described in the spec - add open questions instead.
- The user manages Git. Never run `/commit-and-push` or any git commit/push command yourself, even after a successful loop - always hand off to the user first.
- `/spec` is out of scope for this command - if there are no specs ready to implement, tell the user to run `/spec` and stop.
- Cap dev/review iterations at 5. If the cap is hit, stop and defer to the user instead of looping further.
