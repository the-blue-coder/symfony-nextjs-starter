# Tailwind

### v4 - CSS variable syntax

Use the shorthand `(--var-name)` instead of `[var(--var-name)]`:

```
// ❌ old (v3-style): border-[var(--border-default)]
// ✅ correct (v4):   border-(--border-default)
```

Always use the v4 shorthand - the `[var(...)]` form triggers a deprecation warning.

### Brand tokens - never hardcode hex values

Always use brand token classes - no raw hex values in templates or CSS.

| Role | Tailwind class / CSS variable | Value |
| --- | --- | --- |
| Page background | `--bg-base` | `#[hex]` |
| Surface | `--bg-surface` | `#[hex]` |
| Primary text | `--text-primary` | `#[hex]` |
| Muted text | `--text-muted` | `#[hex]` |
| Primary accent | `--accent-primary` | `#[hex]` |
| Border | `--border-default` | `#[hex]` |
| Error | `--state-error` | `#[hex]` |
| Success | `--state-success` | `#[hex]` |

### Responsive — CRITICAL

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
