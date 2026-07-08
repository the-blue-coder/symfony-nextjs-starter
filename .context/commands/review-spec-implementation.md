---
description: "Verify that the implemented code matches its feature spec - checks every acceptance criterion, data model, and API contract"
argument-hint: "<spec number or name fragment>"
---

Verify that the implementation matches its feature spec. Every acceptance criterion must be traceable to real code.

Spec to verify: `$ARGS`

---

## Step 1 - Find the spec

List files in `.context/feature-specs/`.

- If `$ARGS` is provided, match by numeric prefix or name fragment (case-insensitive).
- If omitted, show specs with `status: in-progress` or `status: done` and ask the user to pick one.
- If none found: "No implemented specs to verify. Run `/dev` first."

Read the full spec file.

---

## Step 2 - Load context (silent)

Read:
- `.context/architecture.md`
- `.context/coding-conventions/global.md`
- If the spec touches `frontend/` → `.context/coding-conventions/typescript.md`, `.context/coding-conventions/nextjs.md`, `.context/coding-conventions/tailwind.md`, `.context/coding-conventions/ui.md`
- If the spec touches `backend/` → `.context/coding-conventions/php.md`, `.context/coding-conventions/symfony.md`

---

## Step 2.5 - Delegate verification to a specialized subagent

If you were spawned by another command to execute only a subset of these steps, skip this delegation and go straight to Step 3.

Otherwise, launch a subagent specialized for spec verification (agent type: `spec-verifier`, if your tool supports named subagent types - otherwise a general coding subagent) to execute Steps 3 through 6 below against the spec file. Wait for its structured report, then continue to Step 7.

---

## Step 3 - Verify each acceptance criterion

For each `- [x] criterion` (checked) and `- [ ] criterion` (unchecked) in the spec, find the code that satisfies it.

**How to verify each criterion:**

- Search for the relevant entity, route, component, hook, or service that implements it.
- Read the actual implementation - don't assume it exists because the box is checked.
- Confirm the behavior matches the criterion exactly (field names, HTTP method, response shape, access rules, edge case handling).

Assign one of three verdicts per criterion:

| Verdict | Meaning |
| --- | --- |
| ✅ PASS | Code found and behavior matches the criterion |
| ⚠️ PARTIAL | Code exists but behavior is incomplete or differs from the spec |
| ❌ FAIL | No implementation found, or behavior contradicts the criterion |

---

## Step 4 - Verify the data model

For each entity and field listed in the spec's **Data Model** table:

- Find the Doctrine entity class in `backend/src/Entity/`.
- Check that each field exists with the correct type, constraints (nullable, length, etc.), and lifecycle hooks.
- Check that the corresponding TypeScript type in `frontend/src/lib/types.ts` matches.

Verdict: ✅ / ⚠️ / ❌ per entity.

---

## Step 5 - Verify the API contract

For each route in the spec's **API Contract** table:

- Find the corresponding controller or API Platform resource.
- Confirm: method, route path, request body shape, response shape, auth requirement (`ROLE_USER`, etc.).
- For API Platform: check the `#[ApiResource]` operations, serialization groups, and security attributes.

Verdict: ✅ / ⚠️ / ❌ per route.

---

## Step 6 - Report

Output a structured report:

### Verification report - NNN Feature Name

**Acceptance Criteria**

| # | Criterion | Verdict | Evidence |
| --- | --- | --- | --- |
| 1 | criterion text | ✅ PASS | `path/to/file.ext:line` |
| 2 | criterion text | ⚠️ PARTIAL | found in X but missing Y |
| 3 | criterion text | ❌ FAIL | no implementation found |

**Data Model**

| Entity | Verdict | Notes |
| --- | --- | --- |
| Foo | ✅ | - |

**API Contract**

| Route | Verdict | Notes |
| --- | --- | --- |
| POST /api/foos | ⚠️ | missing auth check |

**Summary**

- X / Y criteria passing
- Overall: ✅ COMPLETE / ⚠️ INCOMPLETE / ❌ FAILING

---

## Step 7 - Convention review

Run `/review-changes` on the files changed by this feature (scope = `frontend`, `backend`, or `all` depending on what the spec touched). This checks coding conventions - hook/component split, braces, prop type naming, repo injection, etc.

Fix all violations before proceeding to Step 8.

---

## Step 8 - Fix or flag

**If all spec verdicts are ✅ and conventions are clean:**
- Update any unchecked `- [ ]` criteria in the spec to `- [x]`.
- Update `status` to `done` and update `.context/progress-tracker.md` accordingly.
- Tell the user: "Spec fully verified and conventions clean - marking as done."

**If any spec verdict is ⚠️ or ❌:**
- Do NOT mark the spec as done.
- For each gap, ask the user: "Do you want me to fix this now, or log it as an open question in the spec?"
  - Fix now → implement the missing piece inline, then re-verify that criterion.
  - Log it → add to the spec's **Open Questions** section: `- [ ] [criterion text] - not yet implemented`.

---

## Step 9 - Commit and push

Once the spec is marked `done`, tell the user:
> Before pushing, do a quick manual scan of the diff (`git diff HEAD`) to catch anything automated review may have missed - dead code, stray debug logs, TODO comments, or anything that looks off. Once satisfied, run `/commit-and-push` to commit and push these changes.
