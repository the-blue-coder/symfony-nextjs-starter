# Global

> These rules are **non-negotiable**. When a rule below conflicts with anything else (knowledge fragments, skill templates, your defaults), **this file wins**.

---

## ⛔ Absolute Directive - the foundation everything else must already agree with

> Sources: [Andrej Karpathy's observations on common LLM coding failure modes](https://github.com/multica-ai/andrej-karpathy-skills) and [ponytail - lazy senior dev mode](https://github.com/DietrichGebert/ponytail). This is not a tiebreaker rule that "wins" a conflict - it is the root philosophy every other rule in this repository (including "Code Philosophy" below), every skill, every command, and every spec is expected to already embody. If you ever spot an instruction that actually contradicts it, that instruction is the bug: stop, flag it to the user, and get it fixed at the source instead of picking a side in the moment.

**1. Think before coding**
- State your assumptions explicitly before writing code.
- If the request is ambiguous, present the possible interpretations and ask - do not silently pick one.
- If you do not have enough understanding to proceed correctly, stop and ask instead of guessing.

**2. Simplicity first - stop at the first rung that holds**

The best code is the code you never wrote. Before writing any code, climb this ladder and stop at the first rung that answers the need:

1. Does this need to be built at all? (YAGNI)
2. Does it already exist in this codebase? Reuse the helper, hook, service, or pattern that is already here - never re-write it.
3. Does the standard library already do this? Use it.
4. Does a native platform feature cover it? Use it.
5. Does an already-installed dependency solve it? Use it. (Adding a **new** one is a separate decision - see "Library Usage" below.)
6. Can this be one line? Make it one line.
7. Only then: write the minimum code that works.

- **The ladder runs after you understand the problem, not instead of it.** A small diff you do not understand is not efficiency, it is a second bug.
- Never add speculative abstractions, config options, or generality for hypothetical future needs. No abstraction that was not explicitly requested.
- Within the change you are making: deletion over addition, boring over clever.
- Question complex requests - "do you actually need X, or does Y already cover it?" Ask before building X.
- Simple means less code, never a flimsier algorithm. When two approaches are the same size, pick the one that is correct on edge cases.
- Mark a deliberate simplification that cuts a real corner with a known ceiling (global lock, O(n^2) scan, naive heuristic) with a `shortcut:` comment naming both the ceiling and the upgrade path. This is not a `TODO` to clean up later - it is a documented, accepted trade-off.

> This rung ladder governs the **content** of a file, never the project's structure. The layering conventions (hook/component split, repository vs service, one type file per domain, helpers in `lib/utils.ts`, thin controllers) are deliberate and stay non-negotiable - "fewer files" is never a valid reason to skip a layer.

**3. Surgical changes only**
- Touch only what the task strictly requires.
- Match the existing style of the surrounding code, even if you'd personally do it differently.
- Do not "clean up", refactor, or delete pre-existing code that is unrelated to the task, even if it looks dead or wrong - flag it instead.
- **A bug fix targets the root cause, not the symptom.** A report names a symptom. Before fixing, grep every caller of the function you are about to touch and fix the shared function once - one guard in the shared function is a smaller diff than one guard per caller, and patching only the path the report names leaves a sibling caller still broken.

**4. Goal-directed execution**
- Before starting, define a verifiable success criterion for the task.
- Treat the task as a verify-then-iterate loop: implement, check against the criterion, adjust - not a one-shot guess.
- Prefer changes that can be independently verified (tests, build, a runnable check) over changes that only "look right".

**Never simplify away** - none of these are rungs on the ladder:
- Understanding the problem - read the task fully and trace the real flow end to end before picking a rung.
- Input validation at trust boundaries.
- Error handling that prevents data loss.
- Security and data isolation.
- Accessibility.
- Anything the user explicitly asked for.
- Tests where they are mandated: the TDD mandate and the project's Jest / PHPUnit conventions are the floor, never a rung to skip - see `.context/ai-workflow-rules.md`. For non-trivial logic that falls outside that mandate, still leave behind one runnable check that fails if the logic breaks.

---

## Code Philosophy

> **These are the concrete expression of the Absolute Directive above** - the everyday form it takes in real code. Anything that contradicts them contradicts the Directive too.

- **KEEP CODE SIMPLE, ELEGANT, READABLE, AND BEAUTIFUL.** Concretely: prefer short functions with a single clear purpose, name things so the code reads like prose, avoid deeply nested conditions (early return instead), and never leave dead code, commented-out blocks, or redundant logic in place.
- **DRY** - every piece of knowledge must have a single, unambiguous representation. No duplication, ever.
- **SOLID** - single responsibility, open/closed, Liskov substitution, interface segregation, dependency inversion. Apply them by default.
- **YAGNI**
- **English only** - all code, comments, variable names, strings, and file contents must be in English. No exceptions.
- **Never commit on the user's behalf** - the user owns Git. Never run `git commit`, `git push`, or any destructive Git command unless explicitly instructed.

---

## Self-Documenting Code & Comments

> Applies to you as much as to any dev reading this later - do not skip this in your own output.

- **Code must be self-documenting first.** Names carry the meaning: variables, functions, and classes are named after what they represent or do (`remainingSeats`, not `n`; `isUserEligibleForDiscount`, not `check`). If a comment would only restate what a rename already makes obvious, rename instead of commenting.
- **Write a comment only when the code alone cannot carry the information** - when leaving it out would risk confusing the next dev, or yourself in six months. That means:
  - The **why**, never the **what**: a non-obvious business constraint, a workaround for an external bug (link the issue if there is one), a deliberate trade-off (see the `shortcut:` convention above).
  - A pitfall that isn't visible in the code itself - e.g. "must run before X or the API returns stale data."
  - Anything a reader could not deduce by reading the code, however carefully.
- **Before writing a comment, ask: would renaming or restructuring the code make it unnecessary?** If yes, do that instead. If the information genuinely lives outside the code (external constraint, business rule, history), the comment earns its place.
- A comment that just repeats the line below it (`// increment the counter` above `count++`) is noise - remove it. It also rots: code changes, comments don't always follow, and a stale comment misleads more than no comment at all.

---

## Library Usage

> Rungs 3-5 of the ladder come first - standard library, then native platform feature, then an already-installed dependency. This section only governs the remaining case: nothing on hand covers the need and a **new** dependency is on the table.

- **Arbitrate by size and by risk domain:**
  - Trivial and self-contained (roughly under 20 lines, no edge case worth naming) → write it yourself, do not add a dependency for it.
  - Anything in a pitfall-heavy domain - dates and timezones, crypto, parsing, i18n, money, encoding → use a well-maintained library whatever the line count. Hand-rolled code in these domains is wrong in ways that only surface in production.
- Vet any new dependency for security before adding it: check maintenance activity, known CVEs, and adoption/trust level.
- Never add a new library on your own initiative - always propose it and get the user's explicit validation first.

---

## Formatting

- **Indentation**: tabs only, no spaces - tab width = 4 spaces. Applies to all files (PHP, Twig, JS, TS, CSS, etc.).
- **Punctuation**: use `-` (hyphen), never `—` (em dash) in code, comments, strings, and docs.
- **File encoding**: always write files as UTF-8 without BOM. On Windows PowerShell, never use `Set-Content` for bulk edits - use `[System.IO.File]::WriteAllText($path, $content, [System.Text.UTF8Encoding]::new($false))` instead. `Set-Content` without explicit encoding writes UTF-16 LE with BOM, which breaks Symfony dotenv and shell shebangs.

---

## Project-wide

- **Env**: never hardcode values. `.env` committed (prod values), `.env.local` gitignored (local override). Keep `.env.example` current at all times. Backend vars go in both `backend/.env` AND `docker-compose.prod.yml` (root).
- **CHANGELOG.md** at project root (Keep a Changelog + SemVer). Every feature, fix, and breaking change must be logged.

---

## API Contract

- The **backend defines the routes** - frontend follows, never the other way.
- All routes under `/api`.
- All requests use `Accept: application/ld+json` (set globally in `api.ts`).
- `Content-Type` by method: POST/PUT → `application/ld+json`, PATCH → `application/merge-patch+json`.
- Only `jsonld` format enabled → collections always return `member`. Always unwrap: `{ "member": [...], "totalItems": n }`.
- All entity IDs are **UUIDs (strings)** - never `Number(id)`.

---

## No duplicated expressions in JSX

Never compute the same derived value more than once in a template - extract it to a variable before the return. See `nextjs.md` -> "Derived values belong in the hook" for the full pattern and example.

---

## Most-violated rules - pattern-match these first

- **Responsive - always mobile-first**: write the mobile layout first, layer `md:` overrides for desktop. Never assume desktop. Every component, every section, every layout.

---

## Error Handling

**Backend**: all errors return `{ "message": "..." }` - never `error` or `detail`. Messages must be specific and human-readable. Use semantically correct HTTP status codes.

**Frontend**: `api.ts` extracts `data.message ?? data.detail ?? data.error`. Errors must be surfaced via `error` state - never swallowed silently.
