---
description: "Enforces the spec-driven development pipeline for this project"
---

# Workflow Pipeline Rules

Two paths depending on scope:

## Quick path - small fixes, bugs, debug, typos

Write code directly. No spec required. Qualifies if **all** of the following are true:
- Touches ≤ 3 files
- No new feature, no API contract change, no DB migration
- Can be described in one sentence
- **No spec is currently `status: in-progress`** - if one exists, run `/review-spec-implementation` first

## Feature path - anything consequent

```
/plan → /dev → /review-spec-implementation
```

- **No code without a spec.** Never write feature code in `frontend/` or `backend/` without a spec in `.context/feature-specs/` with `status: todo` or `status: in-progress`. Run `/planify` first.
- **No `/dev` with pending `/review-spec-implementation`.** Before starting `/dev` on any spec, check `.context/feature-specs/` for specs with `status: in-progress` that have unchecked acceptance criteria (`- [ ]`). If any exist, run `/review-spec-implementation` on them first.
- **Only `/review-spec-implementation` marks specs done.** Never set `status: done` on a spec during `/dev`. Only `/review-spec-implementation` may do this.
- **`/planify` is always allowed.** You may run `/planify` at any time regardless of pipeline state.

## How to check before writing feature code

Before writing any file in `frontend/` or `backend/` for a feature, verify:

```bash
# Check for in-progress specs with unchecked criteria
grep -l "^status: in-progress" .context/feature-specs/*.md | xargs grep -l "^- \[ \]"
```

If any results appear, stop and tell the user:
> Pipeline gate: run `/review-spec-implementation` on the in-progress spec before writing feature code.
