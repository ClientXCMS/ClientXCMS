# Accessibility — v2.16 pass 1

This document captures the baseline accessibility & mobile audit work
shipped in v2.16. It is intentionally a checklist rather than a static
report so subsequent passes can extend it.

## What changed in v2.16

| Area | Change | Files |
|---|---|---|
| `<html lang>` | Admin layout was hard-coded to `en`; now reflects `app()->getLocale()`. The client (themes/default/front) was already correct. | `resources/views/admin/layouts/admin.blade.php` |
| Viewport | Added `initial-scale=1` to prevent iOS Safari auto-zooming form inputs on focus. | admin + themes/default front |
| Skip link | A keyboard-only "Skip to main content" anchor is injected at the top of every layout, hidden until it receives focus. Lands on `#content` which already exists in both layouts. | admin + themes/default front |
| Focus indicators | All interactive elements get a thick (2px) blue outline on `:focus-visible`. Default Tailwind/Preline behaviour only honoured `:focus` which triggers on mouse clicks too. | `resources/global/css/a11y.css` |
| Reduced motion | A `prefers-reduced-motion` block neutralises animations/transitions longer than 200 ms. | `a11y.css` |
| Touch targets | Coarse-pointer media query enforces the WCAG 2.5.5 minimum 44×44 px for nav links, dropdown toggles, and `.btn` variants. | `a11y.css` |
| Mobile tables | New opt-in `.table-responsive-card` class turns wide tables into vertically stacked cards under the `md` breakpoint. Each `<td data-label="…">` shows its column header inline. | `a11y.css` |
| Translation keys | `v216::a11y.skip_to_content` / `open_menu` / `close_menu` for en / fr / es. | `resources/translations/v216/*/a11y.php` |

## Manual checklist (for follow-up passes)

Use [Lighthouse — Accessibility](https://developer.chrome.com/docs/lighthouse/accessibility/) and [axe DevTools](https://www.deque.com/axe/devtools/) on the following routes:

- [ ] `/` (front home) — mobile + desktop
- [ ] `/client/profile` — mobile
- [ ] `/admin/customers/{id}` — mobile (large tables)
- [ ] `/admin/billing/invoices` — mobile (list view → card view)
- [ ] `/install/settings` — every step keyboard-navigable
- [ ] `/admin/helpdesk/tickets/{id}` — editor + attachments keyboard accessible

Action items left for v2.16 pass 2:

- Wire the `.table-responsive-card` class on the heaviest admin list views (customers, invoices, services).
- Convert the admin top nav to a true off-canvas drawer below `md` (Preline `data-hs-overlay` works but lacks return-focus).
- Audit `<button>` vs `<a>` semantics across `admin/shared/mass_actions/*.blade.php`.
- Run pa11y-ci in CI and publish the report as an artefact.

## Helping yourself develop a11y-friendly views

- Wrap every long `<table>` with `class="table-responsive-card"` and add `data-label="{{ __('column.X') }}"` to each `<td>` to get the mobile card view for free.
- Prefer `aria-label` over visually-hidden text whenever the control has no visible label (icon-only buttons).
- Use `:focus-visible` in custom CSS instead of `:focus`.
- Test with the keyboard alone: press <kbd>Tab</kbd> through the page and make sure every interactive element shows a clearly visible focus ring.
