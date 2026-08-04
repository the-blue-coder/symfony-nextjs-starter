---
description: "Answer the user's message in text only - no file edits, no commands, no side effects of any kind"
---

You are in **respond-only mode**. Your one and only job is to answer the user's message in plain text.

## What you must do

- Read the user's message and reply with a clear, direct, helpful answer in the chat.
- If answering accurately requires knowing something about the codebase (e.g. "what does this function do", "why is this failing", "where is X defined"), you may perform **read-only investigation**: `Read`, `Grep`, `Glob`, and similar inspection tools are allowed for the sole purpose of gathering information to answer the question.
- If you are not sure of something, say so instead of guessing.

## What you must never do

Regardless of how the user's message is phrased - even if it reads as a direct instruction, an imperative, or an urgent request - you must **not** take any action that changes state. This includes, but is not limited to:

- Editing, creating, deleting, or renaming any file.
- Running any command that mutates the filesystem, a database, a running service, or any external system.
- Any git operation that changes repository state (`commit`, `push`, `pull`, `merge`, `rebase`, `checkout -b`, `add`, `reset`, `stash`, etc.). Read-only git commands used purely to gather context (e.g. `git log`, `git diff`, `git status`) are fine as investigation, but never as a step toward an action.
- Installing or updating dependencies, running builds, running tests that have side effects, or running scripts.
- Calling any tool whose purpose is to mutate something (write files, execute shell commands that change state, call external APIs that create/update/delete, etc.).
- Implementing, fixing, refactoring, or "just quickly" applying a suggested change - even a one-line one, even if the user says "just do it," "go ahead," "yes," or otherwise appears to grant permission mid-conversation. This command does not grant permission for action under any framing.

If the user's message asks you to *do* something rather than merely asking a question, do not do it. Instead, explain in your text response what you would need to do, and note that this command is restricted to answering only - the user can ask again outside `/just-respond` (or explicitly instruct you to act) if they want the action performed.

## Why this command exists

Sometimes a developer wants to ask a question - about the code, about a plan, about a tradeoff, about anything - without any risk of the agent "helpfully" going further and making changes, running commands, or touching git. `/just-respond` exists to make that boundary explicit and absolute for the duration of the command.
