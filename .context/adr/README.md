# ADR + Context - persistent memory for AI agents

An AI agent starts from zero every session. Without a written trace, it re-proposes an approach that was already tried and rejected, or works from stale context.

This directory gives the project two layers of memory, both plain Markdown, both git-versioned. **There is no `/adr` command** - this protocol runs automatically, as part of the mandatory read order in `.context/ai-workflow-entrypoint.md`, the same way the rest of `.context/` does.

| Folder | Role | Nature |
| --- | --- | --- |
| `decisions/` | 1 file = 1 settled decision (ADR format) | **Immutable** - replaced, never edited |
| `context/` | 1 file = 1 subject/area (the living "why") | **Living** - edited freely |

- **Decision (ADR)** = how something was settled, and above all what was **rejected** and why.
- **Context** = the current state of a subject, its constraints, what to know before touching it.

ADR here stands for **Architecture Decision Record** - though in practice it covers any structural decision (architecture, process, tooling, convention), not just architecture in the narrow sense.

## Read protocol - before touching a domain (always, automatic)

Before proposing an approach in an area you haven't touched yet this session:

1. Check `.context/adr/context/<Subject>.md` if it exists - current state, constraints, gotchas.
2. Check `.context/adr/decisions/` for any file whose `tags:` or title cover the area.
3. **Never contradict a `status: accepted` decision without flagging it explicitly to the user.** If an approach was already rejected in a decision's "Why not something else" section, do not re-propose it silently.
4. If two active decisions conflict, stop and ask the user - do not pick a side.

## Write protocol - when a structural call is made (always, automatic)

As soon as a structural choice is settled during a session - architecture, process, tooling, or convention, especially one where alternatives were considered and rejected - write it down yourself, without waiting to be asked:

1. Derive today's date (`YYYY-MM-DD`).
2. Create `.context/adr/decisions/<date> - <short title>.md` from `decisions/TEMPLATE.md`.
3. Fill in `tags:` and draft `## Context`, `## Decision`, `## Why not something else`, `## Consequences` from the work just done. Always fill both "Why not something else" and "Consequences" - if either is missing, ask the user rather than inventing it.
4. Fill `affects:` with any spec (`.context/feature-specs/`) or subject the decision impacts.
5. Leave `status: proposed`. **Never write `accepted` yourself** - tell the user a decision was drafted and needs their explicit sign-off before other agents may treat it as binding. Once they confirm, flip `status:` to `accepted` yourself - do not touch anything else in the file.

To supersede an existing decision instead of writing a fresh one:

1. Create the new file the same way as above, but set `supersedes: [[<old filename without extension>]]` in its frontmatter.
2. Flip the old file's `status:` to `superseded`. Never edit any other section of the old file.
3. If the old file had an `affects:` list, tell the user those linked specs/subjects should be reviewed against the new decision.

To create or update a living context note for a subject:

1. If `.context/adr/context/<Subject>.md` doesn't exist, create it from `context/TEMPLATE.md`.
2. If it exists, edit it in place - context files are living, not immutable.
3. Always bump `updated:` to today's date on any change.
4. Keep it short and factual. Never write secrets or credentials into it (it's committed to git).

## The ADR format

Every decision follows the same structure: **Context -> Decision -> Why not something else -> Consequences**. See `decisions/TEMPLATE.md` and `decisions/EXAMPLE.md`.

The field that matters most: **"Why not something else"** (the options that were rejected, and why). It is the one piece of information the code will never contain on its own, and it's what stops an agent from re-proposing a dead end.

### Statuses (the human <-> agent loop)

- `proposed` - the agent drafted the decision, waiting for human validation.
- `accepted` - validated by the user.
- `superseded` - replaced by a more recent decision.

The agent **proposes**, the user **accepts**. An `accepted` decision is never edited or silently contradicted.

### The `affects:` link

A structural decision often changes downstream specs or user stories, rarely the product overview. The `affects:` frontmatter field lists what the decision impacts. When a decision moves to `superseded`, those linked items need to be revisited - that link is exactly what an agent loses without a written trace.

## Ground rules

- An `accepted` decision is **never** edited. It changes? -> new file with `supersedes:`, the old one moves to `superseded`.
- One decision per file, short and telling title.
- Context is living: edit it freely and bump `updated:` on every change.
- Short, factual, versioned (`git`). **No secrets** in these files.

---

*Adapted from the `adr-context-kit` by The Agentic Dev.*
