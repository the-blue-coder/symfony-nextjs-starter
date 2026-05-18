# Global

> These rules are **non-negotiable**. When a rule below conflicts with anything else (knowledge fragments, skill templates, your defaults), **this file wins**.

---

## Code Philosophy

- KEEP CODE SIMPLE, ELEGANT, READABLE, BEAUTIFUL.
- DRY. SOLID.
- All code, comments, and file contents in **English**.
- Never commit on the user's behalf - the user manages Git directly.

---

## Formatting

- **Indentation**: tabs only, no spaces - tab width = 4 spaces. Applies to all files (PHP, Twig, JS, TS, CSS, etc.).
- **Punctuation**: use `-` (hyphen), never `—` (em dash) in code, comments, strings, and docs.
- **File encoding**: always write files as UTF-8 without BOM. On Windows PowerShell, never use `Set-Content` for bulk edits - use `[System.IO.File]::WriteAllText($path, $content, [System.Text.UTF8Encoding]::new($false))` instead. `Set-Content` without explicit encoding writes UTF-16 LE with BOM, which breaks Symfony dotenv and shell shebangs.

---

## Project-wide

- **Env**: never hardcode values. `.env` committed (prod values), `.env.local` gitignored (local override). Keep `.env.example` current at all times. Backend vars go in both `backend/.env` AND `backend/docker-compose.prod.yml`.
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

## Error Handling

**Backend**: all errors return `{ "message": "..." }` - never `error` or `detail`. Messages must be specific and human-readable. Use semantically correct HTTP status codes.

**Frontend**: `api.ts` extracts `data.message ?? data.detail ?? data.error`. Errors must be surfaced via `error` state - never swallowed silently.
