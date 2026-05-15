# UI / UX

### Primitives — shadcn/ui

- Always use **shadcn/ui** for UI primitives (Button, Input, Dialog, Select, etc.) — never build them from scratch.
- Add via CLI: `pnpm dlx shadcn@latest add <component>`.
- shadcn/ui components live in `src/components/ui/` — committed to the repo, edit freely to match the design.

### Icons — lucide-react

- Always use **lucide-react** — never inline SVGs or other icon libraries.
- Import individually: `import { Home, Settings } from "lucide-react"`.
- Size/color via props or Tailwind — never hardcode style attributes.

### Animations — framer-motion

- Use **framer-motion** for all animations (page transitions, list item enter/exit, modals, micro-interactions).
- Prefer `motion.*` components over CSS transitions for anything interactive or sequenced.
- Keep animations subtle — avoid decorative motion that slows the UI.

### Global CSS defaults

```css
* {
    user-select: none;
}

input, textarea {
    user-select: text;
}
```

---

## UI Patterns

### Delete confirmation modals

- Always reuse generic `<DeleteModal>` — never one-off inline modals.
- Props: `isOpen`, `onClose`, `onConfirm`, `isDeleting`, `title`, optional `entityName`, `error`.

### Pending state on list/grid rows

- Any row with an in-progress API call: `opacity-40 pointer-events-none`.
- Track with `Set<string>` state (`pendingIds`) — add before call, remove in `finally`.
- For add actions: boolean `isAdding`, disable the trigger button.

### Submit button loading state — never a spinner

Form submit buttons must **never render a spinner** while submitting. Disabled state via reduced opacity — label stays unchanged.

```tsx
// ❌ wrong
<Button type="submit" disabled={isSubmitting}>
    {isSubmitting ? <Loader2 className="animate-spin" /> : "Save"}
</Button>

// ✅ correct
<Button type="submit" disabled={isSubmitting || isNavigating}>
    Save
</Button>
```

### Loading state on non-form interactive elements

Any non-`<button>` clickable (span, div, anchor) triggering an async action needs both:
- `pointer-events-none opacity-40` Tailwind classes
- `onClick={!isLoading ? handleX : undefined}`

```tsx
// ❌ wrong
<span onClick={handleNoThanks} className="cursor-pointer underline">No, thanks</span>

// ✅ correct
<span
    onClick={!isSubmitting ? handleNoThanks : undefined}
    className={`underline ${isSubmitting ? "pointer-events-none opacity-40" : "cursor-pointer"}`}
>
    No, thanks
</span>
```

### Sidebar active state

Use `isNavActive(href)` from `useDashboardLayout` — highlights on exact match AND any sub-path. Exception: `/dashboard` uses exact match only.

### Numeric inputs — always use `<NumberInput>`

Never use a raw `<Input type="number">`. Always use `<NumberInput>` from `src/components/ui/NumberInput.tsx`.
- Formats in French locale with non-breaking space separators (`12 345`) live during typing.
- Wire via `Controller` from react-hook-form.
- Integers only. If decimals are needed, extend the component.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| Build a Button / Dialog / Select from scratch | `pnpm dlx shadcn@latest add <component>` |
| Inline SVG icon | `import { X } from "lucide-react"` |
| Non-button clickable with async action | `pointer-events-none opacity-40` + `onClick={!isLoading ? handleX : undefined}` |
