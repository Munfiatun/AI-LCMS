# UI Consistency & Design System

R6 establishes a small set of reusable UI primitives for the LMS. The goal is consistency, not a visual rewrite.

## Core principles

- Teacher- and student-facing wording uses Indonesian action/outcome language even when internal model values remain English.
- Primary actions use one indigo treatment; success/publication actions use emerald; destructive actions use rose; cautionary actions use amber.
- Repeated surfaces, badges, alerts, buttons, and form fields should use shared primitives instead of copying long Tailwind class lists.
- Accessibility rules from R3 remain part of the design system: visible focus, minimum learning-control touch targets, reduced motion, and semantic status output.

## Blade primitives

### `<x-ui.button>`

Variants: `primary`, `success`, `secondary`, `warning`, `danger`, `ghost`, `link`.
Sizes: `sm`, `md`, `lg`.
Supports links, form buttons, disabled state, and full-width actions.

### `<x-ui.badge>`

Use `status` when displaying domain status. Standard user-facing labels include:

- `draft` → **Draft**
- `review` → **Perlu Dicek Guru**
- `published` → **Terbit**
- `archived` → **Diarsipkan**
- `active` → **Aktif**
- `completed` → **Selesai**
- `pending` → **Menunggu**
- `failed` → **Gagal**
- `expired` → **Kedaluwarsa**

Use `tone` for non-domain categorical labels such as course category.

### `<x-ui.panel>`

Provides the standard radius, border, dark surface, and optional semantic surface variants. Special hero/visual surfaces may remain bespoke.

### `<x-ui.alert>`

Use for success, warning, danger, and informational notices that deserve a clear status treatment. It exposes `role="status"` and works with the global accessibility runtime.

## Shared form classes

- `.ui-label`
- `.ui-field`
- `.ui-help`
- `.ui-error`
- `.ui-section-title`
- `.ui-section-description`
- `.ui-text-link`

These classes keep labels, fields, help text, validation text, headings, and text links consistent without forcing every form into a monolithic component.

## Reference screens

R6 migrates the Instructor Dashboard, Course Collection, and Course Creation screens first. Other screens can move to these primitives incrementally as they are touched in later roadmap work.

Internal database constants, status values, routes, authorization rules, and publication workflows are not changed by the design system.
