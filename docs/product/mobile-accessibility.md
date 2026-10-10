# Mobile & Accessibility Baseline

R3 establishes a regression baseline for the learning experience on keyboard, screen reader, reduced-motion, high-contrast, and small touch-screen environments.

## Global requirements

- Every page exposes a skip-to-content action and a focusable main landmark.
- Keyboard focus must remain visibly distinguishable.
- Current navigation is communicated with `aria-current` when possible.
- Motion is reduced when the operating system requests `prefers-reduced-motion: reduce`.
- Primary learning controls use comfortable touch targets, including Slidebook navigation and Quiz actions.
- Mobile learning surfaces respect dynamic viewport height and safe-area insets.

## Mobile sidebar

The application sidebar exposes an accessible name, open/close controls, `aria-expanded`, `aria-controls`, Escape-to-close behavior, and focus return to the menu button after closing on mobile.

## Student Quiz

The Quiz experience keeps native radio controls. Runtime accessibility enhancement adds question regions, radiogroup semantics, focus movement when the visible question changes, an accessible timer label, and descriptive question-map controls.

## Slidebook viewer

The Slidebook viewer retains keyboard navigation with Left/Right, Home/End, explicit slide-position controls, progressbar semantics, screen-reader announcements, and focus movement to the current slide title. Slide dots communicate the current step to assistive technology.

## Non-goals

R3 does not change scoring, timers, attempt limits, content authorization, or learning-progress rules. Accessibility improvements must not weaken assessment integrity or publication rules.
