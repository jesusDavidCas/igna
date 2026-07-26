# Phase 1F Prompt

Implement Phase 1F for IGNA Studio ticket workflow integrity:

- Correct broken uploaded-file card layouts across administrator, authenticated-client, and public-tracking ticket views.
- Use one shared responsive file-card partial instead of separate page-specific card structures.
- Choose the compact or wide layout from the actual card container width, preferably with CSS container queries.
- Preserve ticket workflow behavior, authorization, file visibility, document review status, upload validation, storage paths, notifications, routes, dependencies, and environment files.
- Keep the human-approved deliverable and compact sidebar visual direction intact.

Required responsive behavior:

- Compact/narrow cards render file information first, badges second, and actions third.
- Wide cards render full-width file information above a bottom row with badges on the left and actions on the right.
- Long filenames wrap without one-letter-per-line collapse, clipping, or horizontal page scrolling.
- Buttons, badges, rejection controls, and metadata remain inside the card at desktop, tablet, and mobile widths.
- Client and public tracking views use the same visual system as administrator ticket views.

Required verification:

- Graphify-first reconnaissance of ticket file-card renderings.
- Focused feature tests for shared file-card regions across admin, client, and tracking views.
- Full `php artisan test`.
- `npm run build`.
- `git diff --check`.
- Browser or local visual validation of the approved desktop and compact layouts.
- Durable session documentation and human visual test guide.
