# Global

> These rules are **non-negotiable**. When a rule below conflicts with anything else (knowledge fragments, skill templates, your defaults), **this file wins**.

---

## Code Philosophy

> **These are the highest-priority rules. They override everything else.**

- **KEEP CODE SIMPLE, ELEGANT, READABLE, AND BEAUTIFUL.** Concretely: prefer short functions with a single clear purpose, name things so the code reads like prose, avoid deeply nested conditions (early return instead), and never leave dead code, commented-out blocks, or redundant logic in place.
- **DRY** - every piece of knowledge must have a single, unambiguous representation. No duplication, ever.
- **SOLID** - single responsibility, open/closed, Liskov substitution, interface segregation, dependency inversion. Apply them by default.
- **English only** - all code, comments, variable names, strings, and file contents must be in English. No exceptions.
- **Never commit on the user's behalf** - the user owns Git. Never run `git commit`, `git push`, or any destructive Git command unless explicitly instructed.

---

## Library Usage

- Prefer a well-maintained library over a from-scratch implementation - libraries are more battle-tested and robust than custom code.
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

Never compute the same derived value more than once in a template. Extract it to a variable before the return.

```tsx
// ❌ wrong - filter().length computed twice
{items.filter(x => x.active).length > 0 && (
    <span>{items.filter(x => x.active).length}</span>
)}

// ✅ correct - computed once
const activeCount = items.filter(x => x.active).length;
{activeCount > 0 && <span>{activeCount}</span>}
```

---

## Most-violated rules - pattern-match these first

- **Responsive - always mobile-first**: write the mobile layout first, layer `md:` overrides for desktop. Never assume desktop. Every component, every section, every layout.

---

## Error Handling

**Backend**: all errors return `{ "message": "..." }` - never `error` or `detail`. Messages must be specific and human-readable. Use semantically correct HTTP status codes.

**Frontend**: `api.ts` extracts `data.message ?? data.detail ?? data.error`. Errors must be surfaced via `error` state - never swallowed silently.
