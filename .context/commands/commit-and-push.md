---
description: "Stage all changes, write a human commit message, commit, and push to origin"
---

You are committing and pushing the current changes on behalf of the developer.

## Step 1 - Understand what changed

Run these in parallel:

```bash
git status --short
git diff HEAD
git log --format="%s" -10
```

Read the output carefully. Identify the nature of the changes: new feature, bug fix, refactor, config change, docs update, etc.

## Step 2 - Stage everything

```bash
git add -A
```

## Step 3 - Write the commit message

First, read the last 10 commit subjects (`git log --format="%s" -10`). If any of them contain "Co-Authored-By" or AI attribution, look further back until you find commits without it. Mimic the style of those human commits (casing, tone, prefix conventions).

Then write a single commit message line following these rules:

- **Imperative mood** - "add login page", "fix redirect loop", "update Clerk config". Not "added", "fixed", "updated".
- **Lowercase** - no capital first letter.
- **Specific** - name what actually changed, not just "update files".
- **No period** at the end.
- **Under 72 characters**.
- If multiple unrelated things changed, pick the most significant one and mention others briefly: `"add Clerk auth, wire next-intl routing"`.

## Step 4 - Commit and push

**CRITICAL**: the commit message is the plain `-m` string only. No trailers. No `Co-Authored-By`. No `Generated with`. No AI attribution of any kind. A human developer wrote this commit.

```bash
git commit -m "<your message>"
git push origin HEAD
```

Run commit first, then push sequentially (push depends on commit succeeding).

## Step 5 - Confirm

Report the commit hash and message to the user. One line: `pushed <hash> - <message>`.
