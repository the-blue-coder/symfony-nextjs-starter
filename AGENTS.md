**Mandatory read at the start of every session, before any code:**
`.context/ai-workflow-entrypoint.md`

## Responsive — CRITICAL

**Always build mobile-first.** This is non-negotiable.

- Write the mobile layout first, no breakpoint prefix.
- Layer `md:` overrides for desktop. `md:` is the primary desktop breakpoint.
- Never write desktop-only styles and forget the mobile view.
- Every layout, every component, every new section: think mobile first.

```html
<!-- ✅ correct: mobile base, md: override -->
<div class="flex flex-col md:flex-row">

<!-- ❌ wrong: desktop assumed, mobile broken -->
<div class="flex flex-row">
```
