---
type: decision
status: accepted
date: 2026-01-15
tags: [agents, memory, architecture]
supersedes:
affects: "[[knowledge graph]], [[agent recall]]"
---

## Context

AI agents working on this project (Claude Code, Cursor, ...) need persistent, shared memory: decisions, context, content. What format should it be stored in so it survives across sessions AND across tools? Constraint: the tool used changes over time, and the data must stay owned by the project - no vendor lock-in.

## Decision

Memory lives in **version-controlled Markdown files (git)**, under `.context/adr/`. Every tool reads and writes to the same files. No tool "owns" the memory.

## Why not something else

- **Vector database / RAG (pgvector, Pinecone, ...)**: strong for semantic search, but the data is opaque (unreadable embeddings), not hand-editable, and tied to a stack. Overkill for this volume and breaks portability. Rejected.
- **Each tool's own chat history**: memory stays trapped inside one tool, unreadable by the others, lost if the tool is dropped or changes vendor. Rejected.
- **SQLite / structured database**: queryable, but less readable/editable by a human and doesn't diff cleanly in git. Rejected.

## Consequences

- Positive: portable, readable in ten years, hand-editable, versioned (diff, rollback).
- Positive: tool-agnostic - a tool can be added or dropped without losing anything.
- Negative / risk: no native semantic search - mitigated by keeping filenames and `tags:` descriptive enough to grep.
- Negative / risk: requires discipline - decisions and context go stale if they're not written.
- Generates: wiring every tool to read `.context/adr/` before acting (see `.context/ai-workflow-entrypoint.md`).
