# UI Context

## Theme

[Describe the overall visual language - e.g. Dark only.
No light mode. The design language is a dark technical
workspace - near-black backgrounds, layered surfaces,
and vivid accent colors for interactive elements.]

## Typography

| Role      | Font              | Variable      |
| --------- | ----------------- | ------------- |
| UI text   | [e.g. Geist Sans] | `--font-sans` |
| Code/mono | [e.g. Geist Mono] | `--font-mono` |

## Border Radius

| Context           | Class            |
| ----------------- | ---------------- |
| Inline / small UI | `rounded-[size]` |
| Cards / panels    | `rounded-[size]` |
| Modals / overlays | `rounded-[size]` |

## Component Library

[e.g. shadcn/ui on top of Tailwind. Components live
in components/ui/. Use the CLI to add new components
rather than writing from scratch.]

## Layout Patterns

- [Pattern - e.g. Editor: full-viewport split with
  left sidebar, center canvas, right sidebar]
- [Pattern - e.g. Sidebars: fixed width with border separator]
- [Pattern - e.g. Modals: centered overlay with backdrop blur]
- [Pattern - e.g. Navbar: top bar with bottom border]

## Icons

[e.g. Lucide React. Stroke-based icons only. Sizes:
h-4 w-4 for inline, h-5 w-5 for buttons.]
