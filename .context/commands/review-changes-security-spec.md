---
description: "Run all three review commands in sequence: conventions, security, and spec-implementation verification"
argument-hint: "[spec number or name fragment]"
---

# /review-changes-security-spec

Runs the three existing review commands back-to-back, in this order:

1. `/review-changes all` - coding conventions review + fixes, full repo scope (`frontend/` + `backend/`)
2. `/review-security` - security review + fixes
3. `/review-spec-implementation $ARGS` - spec-vs-implementation verification

This is a convenience wrapper. It does not duplicate their logic - it invokes each command's content file in full, one after another, and lets each one run its own Step 0.5/1.5 subagent delegation independently.

Note on scope: `/review-changes` normally takes a `frontend | backend | all` scope argument. This wrapper always calls it with `all`, since it reviews everything pending regardless of layer. `$ARGS` passed to this command is reserved for the spec identifier used in Step 3.

---

## Step 1 - Run the conventions review

Read and execute `.context/commands/review-changes.md` in full (Steps 1 through 6), with scope `all`.

Wait for it to finish and fix all violations before moving on.

---

## Step 2 - Run the security review

Read and execute `.context/commands/review-security.md` in full (Steps 1 through 6).

Wait for it to finish and fix all violations before moving on.

---

## Step 3 - Run the spec-implementation verification

Read and execute `.context/commands/review-spec-implementation.md` in full, passing `$ARGS` through as the spec to verify (same rules apply: if omitted, it will list in-progress/done specs and ask the user to pick one).

Note: this step already runs its own convention review - that's expected and harmless, since Step 1 above will have already fixed everything, so the second pass should find nothing new.

---

## Step 4 - Final summary

After all three commands have completed, output one combined summary to the user:

- Conventions review: table from Step 1, or "no violations"
- Security review: table from Step 2, or "no violations"
- Spec verification: pass/fail counts and overall status from Step 3

Then remind the user:
> Before committing/pushing, do a quick manual scan of the diff (`git diff HEAD`) to catch anything automated review may have missed.
