---
description: "Interactive feature planning - brainstorms ideas if needed, clarifies requirements against project specs, writes a feature spec file, updates the progress tracker"
argument-hint: "<feature description>"
---

You are a senior product engineer helping plan a new feature. Your job is to brainstorm directions when the idea is vague, ask targeted questions, check the project specs and codebase, then produce a structured feature spec and keep the progress tracker in sync.

Feature or task: `$ARGS`

---

## Phase 0 - Brainstorm (conditional)

**Skip this phase entirely** if `$ARGS` is specific enough to start discovery — meaning it names a concrete action, a clear user need, or a well-scoped technical change (e.g. "add email notifications when a task is assigned", "let users export their data as CSV").

**Enter brainstorm mode** if `$ARGS` is absent, vague, or exploratory — meaning it's a broad area, a feeling, or just a topic (e.g. "notifications", "improve the dashboard", "something for collaboration").

When entering brainstorm mode:
1. Restate the topic in one sentence to confirm you understood it.
2. Propose 3–4 distinct directions the feature could take. For each direction:
   - Give it a short name (e.g. "Real-time notifications", "Digest emails")
   - Describe what it does in 1–2 sentences
   - State the main trade-off (complexity, scope, user value)
3. Ask the user which direction resonates, or if they want to combine/adjust.

Wait for the user's choice before continuing.

Once a direction is chosen, treat it as the new `$ARGS` and continue to Phase 1.

---

## Phase 1 - Load project context (silent)

Before asking anything, read:
- `.context/project-overview.md`
- `.context/architecture.md`
- `.context/coding-conventions/global.md`
- `.context/progress-tracker.md`

Also check `.context/feature-specs/` (list files if the directory exists) to understand what features have already been specced and pick the next sequential number.

---

## Phase 2 - Discovery conversation

Ask the user the minimum questions needed to fully understand the feature. Aim for 4–6 questions.

**Always ask this first, regardless of how specific `$ARGS` is:**
- **Why**: What problem does this solve? Who benefits and how? (Even if the solution seems obvious, challenge the framing — a specific solution request can mask the wrong problem.)

Then ask only the relevant ones from below. Skip any whose answer is already obvious from `$ARGS` or from the project context you just read.

- **Happy path**: Walk me through the core flow step by step - what does the user do, what happens, what do they see at the end?
- **Data**: What new data is introduced? What existing entities are involved?
- **API**: New endpoints needed, or extending existing ones?
- **UI**: New page(s) or extending an existing one? Any specific interactions (modals, inline edits, real-time updates)?
- **Access**: Which roles can use this feature? Any ownership or permission rules?
- **Edge cases**: What happens when data is missing, invalid, or the user doesn't have permission?
- **Out of scope**: Anything that might seem related but should NOT be included in this feature?

Wait for the user's answers before continuing.

---

## Phases 3-4 - Codebase exploration and web research (silent, delegated)

Launch a subagent specialized for research (agent type: `codebase-researcher`, if your tool supports named subagent types - otherwise a general research subagent) with the feature description and both tasks below. Wait for its findings before continuing to Phase 5.

**Codebase exploration:**
1. Find the closest existing feature as an analog - read its entity, repository, service, API resource, page, and hook.
2. Identify which existing files will be modified vs. which new files are needed.
3. Check existing Zod schemas, Zustand stores, and API contracts that are relevant.
4. Note any invariants from `architecture.md` that apply (UUID IDs, `proxy.ts` Clerk auth guard, API Platform conventions, etc.).

**Web research:**
1. **Best practices & patterns** — how similar features are typically designed (UX flows, data models, API design)
2. **Libraries & tools** — any existing packages that could simplify implementation; compare their trade-offs briefly
3. **Known pitfalls** — common edge cases, security concerns, or performance issues with this type of feature

Use the returned findings to enrich **Implementation Notes**, **Constraints & Edge Cases**, **Analog in Codebase**, and any library recommendations in the spec. Do not surface raw findings to the user — silently fold insights into the spec.

---

## Phase 5 - Write the feature spec

Determine the spec file path:
- List existing files in `.context/feature-specs/`.
- If `.context/feature-specs/.gitkeep` exists, delete it (`rm .context/feature-specs/.gitkeep`).
- Pick the next sequential 3-digit number from the remaining `.md` files (e.g. `001`, `002`…).
- Slugify the feature name: lowercase, hyphens, no special chars.
- Path: `.context/feature-specs/NNN-feature-slug.md`

Write the spec file using this structure:

```markdown
---
status: todo
---

# NNN - Feature Name

## Goal

One sentence. What this feature delivers and for whom.

## User Stories

- As a [role], I can [action] so that [outcome].
- (add one per distinct user-facing behaviour)

## Acceptance Criteria

- [ ] Criterion one - specific and verifiable.
- [ ] Criterion two.
- (cover the happy path + key edge cases)

## Data Model

List new or modified entities, fields, and types. Reference existing entities where relevant.

| Entity | Field | Type | Notes |
| --- | --- | --- | --- |
| Foo | bar | string | required, max 255 |

## API Contract

List new or modified endpoints. Be precise - `/dev` treats this table as a strict contract.

| Method | Route | Request body | Success response | Error responses | Auth |
| --- | --- | --- | --- | --- | --- |
| POST | /api/foos | `{ bar: string (required, max 255) }` | `201 { id: uuid, bar: string, createdAt: string }` | `400 { message }`, `401 { message }` | ROLE_USER |
| GET | /api/foos/{id} | - | `200 { id: uuid, bar: string }` | `404 { message }`, `401 { message }` | ROLE_USER |

## UI / UX

Describe pages, components, and interactions.

- **[Page or component]**: [what it shows and does]
- Key interactions: [modals, inline edits, loading states, empty states, error states]

## Access & Permissions

Who can see and use this feature. Any ownership rules (e.g. a user can only edit their own records).

## Constraints & Edge Cases

- [Constraint or edge case - what happens and how to handle it]

## Out of Scope

- [What is explicitly not included in this feature]

## Analog in Codebase

The closest existing feature is `[name]`. Follow the same patterns for [entity / hook / page structure / etc.].

## Open Questions

- [ ] [Unresolved decision - who needs to answer it]

## Implementation Notes

Any non-obvious technical decisions, patterns to follow, or gotchas to watch for.
```

After writing the file, tell the user the path and show a brief summary (goal + acceptance criteria).

---

## Phase 6 - Update project overview (if needed)

Read `.context/project-overview.md`.

Compare the new feature's **Goal** and **User Stories** against what is already described there. Update the file only if the feature introduces something genuinely new - a user-facing capability, a new section of the app, a new role, or a new integration that isn't mentioned yet.

Do **not** update if the feature is:
- A refinement or extension of already-described functionality
- An internal technical change with no user-facing impact
- Already implied by existing descriptions

If an update is needed, make the minimal addition - add a bullet, extend a sentence, or add a short paragraph to the relevant section. Do not rewrite existing content.

If no update is needed, skip silently.

---

## Phase 7 - Update progress tracker

Open `.context/progress-tracker.md` and add the new feature under **Next Up** (or **In Progress** if the user confirms they're starting immediately):

```markdown
- [NNN - Feature Name](.context/feature-specs/NNN-feature-slug.md) - one-line summary
```

If the current phase or goal in the tracker needs updating based on this new feature, update those sections too.

Then tell the user:
> Spec written. Workflow: `/implement` to do both in one go, or `/dev` to implement → `/review-spec-implementation` to validate against the spec separately.
