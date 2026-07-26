# Agent memory - successes and failures across sessions

An AI agent starts from zero every session. Without a written trace, it repeats a mistake the user already corrected, or abandons an approach the user already validated. This directory is that trace: plain Markdown, git-versioned, read automatically as part of the mandatory read order in `.context/ai-workflow-entrypoint.md`.

This is **not** the ADR system (`.context/adr/`). ADR records *structural* decisions (architecture, tooling, process) and what was rejected. Memory records *behavioral* feedback about how to work - corrections, validated approaches, and non-obvious project facts that aren't a structural decision.

## Structure

```
.context/memory/
├── README.md          # this file
├── MEMORY.md           # index - one line per entry, always read
├── feedback_<slug>.md  # corrections/successes the user gave about how to work
├── project_<slug>.md   # ongoing project facts not derivable from code/git
└── reference_<slug>.md # pointers to external systems (dashboards, trackers, vaults)
```

There is no `user_<slug>.md` category here: this boilerplate is shared code, not a personal assistant, so per-person preference memory does not belong in a committed repo.

**`MEMORY.md` is an index, not a memory.** It must stay short - keep it under ~150 chars per line and under ~200 lines total. Never write memory content directly into it; only a link and a one-line hook.

## Read protocol - every session (automatic, no command)

1. `MEMORY.md` is in the mandatory read list in `.context/ai-workflow-entrypoint.md` - read it every session, before writing code.
2. Only open an individual `feedback_*.md` / `project_*.md` / `reference_*.md` file when its one-line hook in the index looks relevant to the current task. Do not bulk-read every memory file "just in case" - that defeats the point of keeping the index thin.
3. If a memory conflicts with what you observe in the current code/git state, trust what you observe now, and fix or remove the stale memory (see Consolidation below).

## Write protocol - when it happens (automatic, no command)

Write a memory the moment one of these happens, without waiting to be asked:

- **The user corrects an approach** ("no, don't do X", "stop doing Y") -> `feedback_<slug>.md`, type `feedback`.
- **The user confirms a non-obvious approach worked** (accepts an unusual choice without pushback, "yes exactly, keep doing that") -> `feedback_<slug>.md`, type `feedback`. Confirmations are quieter than corrections - don't only capture failures, or the memory drifts toward excess caution.
- **You learn an ongoing project fact** not derivable from code or git history (a deadline, a stakeholder ask, why work is scoped a certain way) -> `project_<slug>.md`, type `project`.
- **You learn where something lives in an external system** (issue tracker, dashboard, vault) -> `reference_<slug>.md`, type `reference`.

Steps:

1. Check `MEMORY.md` first - if an existing entry covers the same topic, update that file instead of creating a duplicate.
2. Create/update the file with the frontmatter format below.
3. Add or update its one-line pointer in `MEMORY.md`.

### File format

```yaml
---
name: <slug, matches filename without extension>
description: <one-line, specific enough to judge relevance from the index alone>
metadata:
  type: feedback | project | reference
---
```

Body:

- `feedback` / `project`: lead with the rule or fact itself, then a `**Why:**` line (the reason - often a past incident or explicit constraint) and a `**How to apply:**` line (when this should change future behavior).
- `reference`: what the external resource is and when to check it.

Link related memories with `[[other-slug]]`. A link that doesn't resolve yet is fine - it flags something worth writing later, not an error.

### What NOT to save here

- Anything derivable by reading the code, `git log`, or `git blame`.
- Anything already documented in `.context/coding-conventions/` or `.context/adr/`.
- Secrets, credentials, tokens, connection strings - **never**, even for `reference` entries. Point at where the secret lives (a vault, a password manager), never the value itself.
- Ephemeral, single-session task state - that belongs in a `/spec` or the current conversation, not here.

## Consolidation - keep `MEMORY.md` under control

Lines past line 200 of an index-style file are effectively invisible to an agent skimming it, so `MEMORY.md` must not grow unbounded:

- Whenever you add an entry and notice `MEMORY.md` is approaching ~150 lines, or you find entries that are stale, superseded, or redundant, consolidate before moving on:
  - Merge near-duplicate entries into one file and one index line.
  - Delete entries that no longer apply (e.g. a `project_*` fact whose milestone has passed and left nothing actionable behind).
  - Split an index line that has accumulated too much detail back out into its target file - the index line should stay a hook, not a summary.
- This is expected routine maintenance, not a destructive action - no need to ask before pruning an obviously stale entry, but flag anything you're unsure about to the user instead of silently deleting it.
