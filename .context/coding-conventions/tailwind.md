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

### Form fields — label + input spacing

Always wrap a label and its input in `flex flex-col gap-1.5` — never rely on default browser spacing or `mt-*` on the input.

```tsx
// ❌ wrong
<div>
    <Label htmlFor="name">Name</Label>
    <Input id="name" />
</div>

// ✅ correct
<div className="flex flex-col gap-1.5">
    <Label htmlFor="name">Name</Label>
    <Input id="name" />
</div>
```

Error messages go directly after the input — no `mt-*` needed since the gap is already set by the parent.

```tsx
<div className="flex flex-col gap-1.5">
    <Label htmlFor="email">Email</Label>
    <Input id="email" />
    {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
</div>
```

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
