---
description: "Add a new design token to the project (globals.css + @theme inline + tailwind.md documentation)"
argument-hint: "<token-name> <hex>"
---

Add a new design token. Args (optional): `$ARGS` (e.g. `accent-secondary #7C3AED`)

---

## Step 1 - Resolve inputs

**If `$ARGS` is provided**, parse:
- `token-name` = first word (e.g. `accent-secondary`) - should follow the existing semantic-role naming already used in `tailwind.md`'s Brand tokens table (`bg-*`, `text-*`, `accent-*`, `border-*`, `state-*`), not a `brand-*` prefix
- `hex` = second word (e.g. `#7C3AED`)

**Otherwise**, ask:
1. Token name? (e.g. `accent-secondary`) - should follow the existing semantic-role naming already used in `tailwind.md`'s Brand tokens table (`bg-*`, `text-*`, `accent-*`, `border-*`, `state-*`), not a `brand-*` prefix
2. Hex value? (e.g. `#7C3AED`)

Wait for answers before proceeding.

Normalize the hex to **lowercase** before proceeding (e.g. `#E12929` → `#e12929`).

---

## Step 2 - Add the CSS variable

In `frontend/src/app/globals.css`:

1. Under `:root { ... }`, add the new variable:
   ```css
   --<token-name>: <hex>;
   ```

2. Under `@theme inline { ... }`, add the new variable:
   ```css
   --color-<token-name>: var(--<token-name>);
   ```

This makes `bg-<token-name>`, `text-<token-name>`, `border-<token-name>` etc. available as Tailwind utility classes.

---

## Step 3 - Document in tailwind.md

In `.context/coding-conventions/tailwind.md`, find the **Brand tokens** table and add a new row:

```
| <Role label> | `--<token-name>` | `<hex>` |
```

---

## Step 4 - Confirm

Tell the user:
- The CSS variable and `@theme` entry added
- The Tailwind utilities now available (`bg-<token-name>`, `text-<token-name>`, `border-<token-name>`, `<token-name>/50`, etc.)
- The tailwind.md row added
