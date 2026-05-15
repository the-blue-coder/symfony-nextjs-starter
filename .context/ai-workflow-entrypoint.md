# AI Workflow Entrypoint

Start here. Read the files below in order before writing any code.

---

## 1. Mandatory read — every session, before any code

| File | What it gives you |
| --- | --- |
| `.context/project-overview.md` | What the app does, goals, features, scope |
| `.context/architecture.md` | Stack, folder structure, invariants, system boundaries |
| `.context/coding-conventions/global.md` | Golden rules, cross-cutting concerns — **non-negotiable** |
| `.context/progress-tracker.md` | Current phase, completed work, open questions |

## 2. Mandatory read — only when touching the frontend (`frontend/`)

| File | What it gives you |
| --- | --- |
| `.context/coding-conventions/typescript.md` | TypeScript / React rules |
| `.context/coding-conventions/nextjs.md` | Next.js framework rules |
| `.context/coding-conventions/tailwind.md` | Tailwind rules |
| `.context/coding-conventions/ui.md` | Design system, UI patterns |
| `.context/ui-context.md` | Design tokens, layout decisions |

## 3. Mandatory read — only when touching the backend (`backend/`)

| File | What it gives you |
| --- | --- |
| `.context/coding-conventions/php.md` | PHP language rules |
| `.context/coding-conventions/symfony.md` | Symfony / Doctrine rules |

## 4. Mandatory read — only when touching infrastructure (`infra/`, env vars, Docker, deploy)

| File | What it gives you |
| --- | --- |
| `.context/infra.md` | Docker, env vars, nginx, deploy, known gotchas |

---

## 5. Workflow gates

Two paths depending on scope:

### Quick path — small fixes, bugs, debug, typos

```
(no command needed) → write code directly
```

Qualifies as quick if **all** of the following are true:
- Touches ≤ 3 files
- No new feature, no API contract change, no DB migration
- Can be described in one sentence
- **No spec is currently `status: in-progress`** — if one exists, run `/verify` first

Just write the fix. No spec required.

### Feature path — anything consequent

```
/plan → /dev → /verify
```

| Rule | Detail |
| --- | --- |
| No code without a spec | Never write feature code without a spec in `.context/feature-specs/` with `status: todo` or `status: in-progress`. Run `/plan` first. |
| No `/dev` with pending `/verify` | Before starting `/dev` on any spec, check `.context/feature-specs/` for specs with `status: in-progress` that have unchecked acceptance criteria (`- [ ]`). If any exist, run `/verify` on them first. |
| `/verify` owns `done` | Only `/verify` may set `status: done` on a spec. `/dev` never marks a spec done. |
| `/plan` is always allowed | You may run `/plan` at any time regardless of pipeline state. |

**Before writing feature code**, check the current pipeline state:
1. List all specs in `.context/feature-specs/`.
2. If any spec is `status: in-progress` with unchecked criteria → tell the user and suggest `/verify` before proceeding.
3. If no spec covers the requested change → tell the user and suggest `/plan` first.

---

## 6. How to work

See `.context/ai-workflow-rules.md` — scoping rules, protected files, doc sync policy, and the checklist to complete before moving to the next unit.

---

## 7. Maintaining commands and hooks

Command logic lives in `.context/commands/` — that is the single source of truth.

Each tool has thin wrapper files that delegate to the shared source:

| Tool | Commands | Hook |
| --- | --- | --- |
| Claude Code | `.claude/commands/` | `.claude/settings.json` + `.claude/hooks/` |
| opencode | `.opencode/commands/` | — |
| Antigravity | `.agent/skills/` | `.agent/rules/workflow-pipeline.md` |

**When the user asks you to modify a command or hook**, you MUST propagate the change to all tool directories in the same operation. Never leave them out of sync.

- Edit the source in `.context/commands/` first
- Mirror to `.claude/commands/` (add `allowed-tools` frontmatter if needed)
- Mirror to `.opencode/commands/`
- Mirror to `.agent/skills/`
- For hook changes: update `.claude/hooks/enforce-spec-pipeline.sh` AND `.agent/rules/workflow-pipeline.md`
