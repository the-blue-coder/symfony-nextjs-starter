# AI Workflow Entrypoint

Start here. Read the files below in order before writing any code.

> **Project not initialized yet?** If `project-overview.md` or `ui-context.md` still contain `[bracketed]` placeholders, stop and run `/init-project` before proceeding.

> ⛔ **Absolute Directive**: before anything else, read the "Absolute Directive" section at the top of `.context/coding-conventions/global.md` - think before coding, simplicity first (the 7-rung ladder), surgical changes with root-cause fixes, goal-directed execution. It is the foundation every other rule, command, skill, and spec here is expected to already embody; if you find something that contradicts it, that instruction is the bug - flag it instead of picking a side.

---

## 1. Mandatory read - every session, before any code

| File | What it gives you |
| --- | --- |
| `.context/project-overview.md` | What the app does, goals, features, scope |
| `.context/architecture.md` | Stack, folder structure, invariants, system boundaries |
| `.context/coding-conventions/global.md` | Golden rules, cross-cutting concerns - **non-negotiable** |
| `.context/coding-conventions/security.md` | Trust boundaries, auth, webhooks, secrets, CORS - **non-negotiable** |
| `.context/progress-tracker.md` | Current phase, completed work, open questions |
| `.context/ai-workflow-rules.md` | Scoping rules, TDD mandate, protected files, doc-sync policy |

## 2. Mandatory read - only when touching the frontend (`frontend/`)

| File | What it gives you |
| --- | --- |
| `.context/coding-conventions/typescript.md` | TypeScript / React rules |
| `.context/coding-conventions/nextjs.md` | Next.js framework rules |
| `.context/coding-conventions/tailwind.md` | Tailwind rules |
| `.context/coding-conventions/ui.md` | Design system, UI patterns |
| `.context/ui-context.md` | Design tokens, layout decisions |

## 3. Mandatory read - only when touching the backend (`backend/`)

| File | What it gives you |
| --- | --- |
| `.context/coding-conventions/php.md` | PHP language rules |
| `.context/coding-conventions/symfony.md` | Symfony / Doctrine rules |

## 4. Mandatory read - only when touching infrastructure (`infra/`, env vars, Docker, deploy)

| File | What it gives you |
| --- | --- |
| `.context/infra.md` | Docker, env vars, nginx, deploy, known gotchas |

**SSH access to prod**: use the `contabo` host alias (`ssh contabo`) - it is already configured in the local `~/.ssh/config` with the right user and identity file. Do not use `ssh root@<ip>` directly.

---

## 5. Workflow gates

Two paths depending on scope:

### Quick path - small fixes, bugs, debug, typos

```
(no command needed) → write code directly
```

Qualifies as quick if **all** of the following are true:
- Touches ≤ 3 files
- No new feature, no API contract change, no DB migration
- Can be described in one sentence
- **No spec is currently `status: in-progress`** - if one exists, run `/review-spec-implementation` first

Just write the fix. No spec required.

### Feature path - anything consequent

```
/spec → /dev → /review-spec-implementation
```

| Rule | Detail |
| --- | --- |
| No code without a spec | Never write feature code without a spec in `.context/feature-specs/` with `status: todo` or `status: in-progress`. Run `/spec` first. |
| No `/dev` with pending `/review-spec-implementation` | Before starting `/dev` on any spec, check `.context/feature-specs/` for specs with `status: in-progress` that have unchecked acceptance criteria (`- [ ]`). If any exist, run `/review-spec-implementation` on them first. |
| `/review-spec-implementation` owns `done` | Only `/review-spec-implementation` may set `status: done` on a spec. `/dev` never marks a spec done. |
| `/spec` is always allowed | You may run `/spec` at any time regardless of pipeline state. |

**Before writing feature code**, check the current pipeline state:
1. List all specs in `.context/feature-specs/`.
2. If any spec is `status: in-progress` with unchecked criteria → tell the user and suggest `/review-spec-implementation` before proceeding.
3. If no spec covers the requested change → tell the user and suggest `/spec` first.

---

## 6. How to work

See `.context/ai-workflow-rules.md` - scoping rules, protected files, doc sync policy, and the checklist to complete before moving to the next unit.

---

## 7. Maintaining commands, hooks, and subagents

**All commands and hooks must be agent-agnostic**: the logic lives in `.context/` and is mirrored to every tool directory. Never add logic only to one tool's folder.

Command logic lives in `.context/commands/` - that is the single source of truth.

Each tool has thin wrapper files that delegate to the shared source:

| Tool | Commands | Hooks / Rules | Specialized subagents |
| --- | --- | --- | --- |
| Claude Code | `.claude/commands/` | `.claude/settings.json` + `.claude/hooks/` | `.claude/agents/` |
| opencode | `.opencode/commands/` | - | `.opencode/agents/` |

**When the user asks you to modify a command**, you MUST propagate the change to all tool directories in the same operation:

- Edit the source in `.context/commands/` first
- Mirror to `.claude/commands/` (add `allowed-tools` frontmatter if needed)
- Mirror to `.opencode/commands/`

**Specialized subagents have no shared `.context/` source** - each tool defines them natively (`.claude/agents/*.md` with `tools:` frontmatter, `.opencode/agents/*.md` with `mode: subagent` + `permission:` frontmatter). The delegation instructions that reference them (e.g. "launch a subagent specialized for implementation work, agent type: `implementer`") live in `.context/commands/` and stay tool-agnostic - they name the agent generically and fall back to a general subagent if the tool doesn't support named types.

**When the user asks you to add or modify a specialized subagent**, propagate to every tool's native format in the same operation, keeping the persona, rules, and tool/permission restrictions equivalent across formats:

- Create/update `.claude/agents/<name>.md`
- Create/update `.opencode/agents/<name>.md`
- If new, add its name to the relevant delegation step(s) in `.context/commands/`

**When the user asks you to modify a hook**, update `.claude/hooks/<hook>.sh` (Claude Code). If the hook is new, create the file.
