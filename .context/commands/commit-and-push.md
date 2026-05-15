---
description: "Stage all changes, write a human commit message, commit, and push to origin"
---

You are committing and pushing the current changes on behalf of the developer.

## Step 1 - Understand what changed

Run these in parallel:

```bash
git status --short
git diff HEAD
```

Read the output carefully. Identify the nature of the changes: new feature, bug fix, refactor, config change, docs update, etc.

## Step 2 - Stage everything

```bash
git add -A
```

## Step 3 - Write the commit message

Write a single commit message line. Rules:

- **Imperative mood** - "add login page", "fix redirect loop", "update Clerk config". Not "added", "fixed", "updated".
- **Lowercase** - no capital first letter.
- **Specific** - name what actually changed, not just "update files".
- **No AI tells** - no "Co-Authored-By", no "Generated with", no "Claude", no attribution of any kind. Write exactly as a developer would.
- **No period** at the end.
- **Under 72 characters**.
- If multiple unrelated things changed, pick the most significant one and mention others briefly: `"add Clerk auth, wire next-intl routing"`.

## Step 4 - Commit and push

```bash
git commit -m "<your message>"
git push origin HEAD
```

Run commit first, then push sequentially (push depends on commit succeeding).

## Step 5 - Confirm

Report the commit hash and message to the user. One line: `pushed <hash> - <message>`.
