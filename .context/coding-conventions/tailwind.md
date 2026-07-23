# Tailwind

### Class order — matters

Classes (and CSS properties) must follow this order, always:

1. **Position** — `position`, `top`, `right`, `bottom`, `left`, `inset-*`
2. **Display** — `block`, `inline-block`, `flex` (with `flex-direction`, `items-*`, `justify-*`, `gap-*`, etc.)
3. **Sizing** — `w-*`, `h-*`, `max-w-*`, `min-w-*`, `aspect-*`
4. **Margin / Padding** — `m-*`, `p-*`
5. **Text-align** — `text-left` / `text-center` / `text-right`
6. **Font-size** — `text-sm`, `text-lg`, `text-xl`, ...
7. **Line-height** — `leading-*`
8. **Font-family** — `font-sans`, `font-serif`, `font-mono`, ...
9. **Colors** — `bg-*`, text color, gradients (`from-*` / `via-*` / `to-*`)
10. **Borders** — `border-*`, `rounded-*`, `divide-*`
11. **Effects** — `shadow-*`, `opacity-*`, `blur-*`, etc.
12. **Animations / transitions** — `transition-*`, `animate-*`, `duration-*`, `ease-*`
13. **z-index** — always last, no exceptions

Responsive (`md:`, `lg:`, ...) and state (`hover:`, `focus:`, `disabled:`, ...) variants attach immediately after the base utility they modify — never grouped separately at the end.

```html
<!-- ✅ correct -->
<div class="absolute top-0 left-0 flex flex-col gap-2 w-full m-4 p-2 text-center text-sm leading-tight font-sans bg-surface border rounded-md transition-colors z-10">

<!-- ❌ wrong: z-index not last, display before position -->
<div class="flex z-10 absolute top-0 left-0 font-sans text-sm">
```

### v4 - CSS variable syntax

Use the shorthand `(--var-name)` instead of `[var(--var-name)]`:

```
// ❌ old (v3-style): border-[var(--border-default)]
// ✅ correct (v4):   border-(--border-default)
```

Always use the v4 shorthand - the `[var(...)]` form triggers a deprecation warning.

Prefer the mapped Tailwind utility (e.g. `bg-surface`) when the token is registered as a theme color. Use the `(--var-name)` arbitrary-value shorthand only for tokens without a dedicated utility class.

### Hex colors — always lowercase

Write hex color values in lowercase: `#e12929`, not `#E12929`. Applies everywhere: CSS variables, Tailwind arbitrary values, design token tables.

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

### Arbitrary pixel values — convert to the spacing scale

Never write a pixel arbitrary value (`top-[105px]`, `w-[240px]`, ...) when it maps cleanly onto Tailwind's spacing scale (`1` unit = `4px`). Divide the pixel value by 4 and use the scale utility instead.

```html
<!-- ❌ wrong -->
<div class="lg:top-[105px]">

<!-- ✅ correct: 105px / 4 = 26.25 -->
<div class="lg:top-26.25">
```

Only fall back to an arbitrary value (`[Npx]`) when the value doesn't land on a clean scale step.

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
