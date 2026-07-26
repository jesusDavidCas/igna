# Phase 1F Result

## Initial Problem

Ticket file cards were visually inconsistent across administrator, client, and public tracking surfaces. Narrow sidebar cards could squeeze filenames and controls into competing columns, while deliverable document rows needed the approved full-width layout without losing responsive collapse behavior.

## File-Card Inventory

Affected renderings:

- Administrator ticket detail: general project files.
- Administrator ticket detail: client-submitted documents.
- Administrator ticket detail: deliverable documents inside each deliverable section.
- Authenticated client ticket detail: files shared with the client.
- Authenticated client ticket detail: documents sent by the client.
- Public tracking result: files shared through signed tracking context.
- Public tracking result: documents sent through signed tracking context.

## Existing Duplication And Root Cause

The problem came from repeated, route-specific file-card structures and grid layouts that treated filename text, badges, rejection inputs, and actions as peers in cramped horizontal rows. Long filenames and multiple actions could collapse the available text column or push controls outside the card.

## Shared Component Architecture

The shared partial is:

`resources/views/partials/ticket-file-card.blade.php`

The partial now exposes stable regions:

- `data-file-card-info`
- `data-file-card-badges`
- `data-file-card-actions`
- `data-file-actions`

Authorization and route decisions remain outside the visual component. The partial receives the already-approved routes and renders only the actions available to that context.

## Compact Vertical Layout

Compact cards render:

1. File information.
2. Badges and labels.
3. Actions.

The rejection-reason input lives in the action area and gets its own safe row. Actions stack at narrow widths, fill the available width, and do not compete with filename metadata.

## Wide Horizontal Layout

Wide cards use CSS container queries. When the card container is wide enough, file information spans the full top row. Badges sit on the lower left and actions align on the lower right with wrapping. When the container narrows, the same markup collapses automatically to the compact vertical hierarchy.

## Typography And Responsive Behavior

The CSS uses component-scoped classes in `resources/css/app.css`, including:

- `container-type: inline-size`
- `min-width: 0`
- `max-width: 100%`
- `overflow-wrap: anywhere`
- `word-break: normal`
- wrapping badge and action groups

This protects long filenames from min-content collapse and avoids one-letter-per-line rendering for ordinary words.

## Accessibility Decisions

Long original filenames remain available through visible text plus `title` and `aria-label`. Action controls remain semantic links, buttons, and forms. The rejection reason input keeps an associated label. Status is presented as readable badge text, not color alone.

## Files Changed

- `resources/views/partials/ticket-file-card.blade.php`
- `resources/css/app.css`
- `tests/Feature/TicketLayoutDocumentExchangeTest.php`

## Tests

Focused tests cover:

- deliverable documents rendering as wide-capable responsive file cards;
- shared file-card regions in administrator, client, and public tracking views;
- rejection controls remaining inside the file-card action area;
- existing download, visibility, review, rejection, and delete routes remaining present.

Final Phase 1 closure verification included:

- `php artisan test`: 79 tests, 656 assertions.
- `npm run build`: passed.
- `git diff --check`: passed.

## Local Visual QA

The human confirmed the local visual design after Phase 1F:

- deliverable cards render correctly;
- compact sidebar cards render correctly;
- approved ticket file and deliverable card design should not be redesigned during Phase 2.

## Human Visual Test Guide

1. Administrator general files: visit `/admin/tickets/{ticket}` on desktop and mobile; expect file information, then badges, then actions in the sidebar.
2. Administrator client-submitted documents: use a document with pending/downloaded/reviewed/rejected states; expect status badges above action controls and no squeezed rejection input.
3. Administrator deliverable documents: inspect deliverable sections in the main content area; expect full-width file information above a lower badges/actions row on wide containers.
4. Authenticated client ticket detail: inspect shared files and sent documents; expect the same compact/wide card rules according to container width.
5. Public tracking: submit a valid local tracking lookup; expect shared files and sent documents to use the same card system without public-only markup drift.
6. Long filenames: use a very long filename and a long uninterrupted filename; expect wrapping inside the card, no one-letter-per-line collapse, no clipping, and no horizontal page scroll.
7. Actions: check Download, show/hide, Mark reviewed, Reject document, and Delete where authorized; expect every control to remain inside the card and in keyboard order.

## Remaining Limitations

- Phase 1F did not add a visual regression test harness; browser/human visual validation remains the source of truth for pixel-level layout approval.
- The shared partial is still Blade-based rather than a class-based View Component, matching the current project convention and keeping the change narrow.
