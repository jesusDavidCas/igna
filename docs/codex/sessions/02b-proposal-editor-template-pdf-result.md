# Phase 2B - Proposal Editor, Reusable Templates, and PDF Resilience Result

## Starting State

- Active branch: `feat/proposal-admin-information-architecture`.
- Phase 1 checkpoint: `1824b89`.
- Phase 2A was uncommitted and included proposal administration restructuring plus a proposal-document feature that was later removed by this phase.
- Graphify was queried for proposal controller/request/model, service-template, public proposal, PDF, and Blade surfaces. Graphify was not regenerated.

## Removed Proposal Documents

Removed the Phase 2A General proposal documents feature because IGNA proposals now treat the generated quote/proposal PDF as the proposal document.

Removed:

- `app/Http/Controllers/Admin/ProposalDocumentController.php`
- `app/Http/Requests/Admin/ProposalDocumentRequest.php`
- `app/Models/ProposalDocument.php`
- `database/migrations/2026_07_25_000300_create_proposal_documents_table.php`

Also removed:

- Admin document upload/download/delete routes.
- Proposal `documents()` relation.
- Proposal create/edit/show document UI.
- Document-only translations and tests.
- Local QA storage under `storage/app/private/proposals/1/documents`.

The local `proposal_documents` table was already absent when inspected.

## Final Section Order

The proposal create/edit form now uses:

1. Proposal identity and status.
2. Client information.
3. Scope and deliverables.
4. Payment schedule and totals.
5. Cost items.
6. Signer and publication.

Calculation behavior remains unchanged even though payment/totals now appears before cost items.

## Restricted Rich Text

Added `App\Support\Proposals\ProposalContentSanitizer` for proposal description and scope.

Allowed tags:

- `p`
- `br`
- `strong`
- `em`
- `ul`
- `ol`
- `li`

Legacy `b` and `i` are normalized to `strong` and `em`. Unsafe tags, attributes, comments, inline styles, event handlers, links, media, tables, forms, and pasted Office-style attributes are removed. Plain-text legacy content is converted to paragraph HTML for consistent rendering.

Editor behavior:

- Compact toolbar for bold, italic, bulleted list, numbered list, and clear formatting.
- Contenteditable editor with synchronized hidden form value.
- Paste sanitization in the browser.
- Server-side sanitization on create and update.
- Retains old rich-text input after validation failure.
- Renders sanitized HTML in admin show, public proposal view, and PDF.

## Service Templates

Template data remains sourced from `proposal_service_templates` and `proposal_service_template_items`, seeded by `database/seeders/ProposalServiceTemplateSeeder.php`.

The existing Services admin route is linked as the management source:

- `GET /admin/services` (`admin.services.index`)

The editor now provides:

- Select service template.
- Number of copies, clamped to 1 through 20 per action.
- Add template items.
- Duplicate confirmation when the selected template is already present in appended rows.
- Repeated insertion of the same template.
- Preservation of existing and manually edited rows.
- Independent row removal and recalculation after appended rows.

## Sorting

The proposal index uses the Created at table header as the compact sort link:

- `Created at ↓` for current newest-first ordering.
- `Created at ↑` for current oldest-first ordering.
- Translated `aria-label` and `title` values.
- Search, status filters, pagination, and safe direction normalization are preserved.

## PDF Resilience

The PDF continues to use Dompdf in `Admin\ProposalController@pdf`.

Changes:

- Removed truncation from description, scope, item descriptions, title, subject, and client name.
- Rich text renders safely in the PDF.
- Proposal-item descriptions wrap naturally.
- Table headers remain in `thead` for multipage table rendering where supported.
- Signature/totals balance adjusted to approximately 32% signature and 68% totals.
- Totals remain on the right, with subtotal/tax/total readable.
- Additional pages are allowed when content requires them.

Tested local PDF artifacts:

- `output/pdf-review/phase-2b/IGNA-2026-P2B-SHORT.pdf` rendered as 1 page.
- `output/pdf-review/phase-2b/IGNA-2026-P2B-LONG.pdf` rendered as 3 pages.
- Rendered PNG pages saved under `output/pdf-review/phase-2b/rendered/`.

`pdftotext` was not installed locally, so PDF text extraction was unavailable. Visual rendered-page inspection and `pdfinfo` page counts were used.

## Warning Thresholds

Non-blocking warning thresholds:

- Rich Detailed description: 1,400 characters.
- Rich Scope: 1,000 characters.
- Item description: 420 characters.
- Template copies: 20 maximum per action.

Warnings explain that long content may continue onto another PDF page and that the complete text remains included. They do not block saving.

## Browser QA

Browser screenshots were saved under `output/ui-review/phase-2b/`.

Routes inspected:

- `/admin/proposals`
- `/admin/proposals/create`
- `/admin/proposals/2/edit`
- `/admin/proposals/2`
- `/admin/proposals/2/pdf`
- Public proposal token route for the QA proposal.

Viewports:

- 1440x900
- 1280x800
- 1024x768
- 768x900
- 390x844

Representative English create/edit screenshots were also captured. Browser manifest: `output/ui-review/phase-2b/browser-validation.json`.

## Tests

Focused and regression coverage included:

- Proposal list compact created-at sorting.
- Removed proposal-document feature.
- Six-section order.
- Rich-text sanitization and rendering.
- Old rich-text input after validation failure.
- Service-template append/copies controls.
- Proposal PDF route availability.
- PDF template complete-content rendering.
- Proposal calculation and admin regressions.
- Phase 1 ticket workflow and layout regressions.

## Human Visual Test Guide

1. Proposal list sort arrows:
   - Role: admin.
   - Route: `/admin/proposals`.
   - Spanish label: `Fecha de creación`.
   - English label: `Created at`.
   - Action: click the arrow header.
   - Expected: newest/oldest order toggles.
   - Failure: large newest/oldest pill appears or order does not change.

2. Removed general-document section:
   - Role: admin.
   - Routes: create, edit, show.
   - Spanish label: none expected.
   - English label: none expected.
   - Action: scan page.
   - Expected: no general proposal documents area.
   - Failure: upload/download/delete document UI appears.

3. Final six-section order:
   - Role: admin.
   - Routes: create and edit.
   - Expected: identity, client, scope, payments, cost items, signer.
   - Failure: cost items appears before payments or documents appear.

4. Rich text:
   - Role: admin.
   - Route: create or edit.
   - Actions: bold, italic, bullet list, numbered list, paste unsafe HTML.
   - Expected: allowed formatting remains; unsafe tags/attributes disappear after save.
   - Failure: scripts, links, styles, images, or event handlers render.

5. Templates:
   - Role: admin.
   - Route: create or edit.
   - Action: add one template, add same template again, add two copies.
   - Expected: rows append without replacing manual rows; duplicate confirmation appears.
   - Failure: existing rows disappear or totals stop updating.

6. PDF:
   - Role: admin.
   - Route: `/admin/proposals/{proposal}/pdf`.
   - Action: generate short and long proposals.
   - Expected: short fits one page, long continues across pages, all rows remain, totals are wide on the right, signature is left.
   - Failure: clipped text, missing rows, overlapped totals, distorted signature, or unreadably small text.

7. Responsive layout:
   - Role: admin.
   - Routes: index, create, edit, show, PDF, public proposal.
   - Widths: desktop, tablet, mobile.
   - Expected: no body-level horizontal overflow.
   - Failure: page-level sideways scrolling or clipped controls.

## Remaining Limitations

- No new service-template management module was added. The existing Services admin route is linked, while template rows remain seeded/model-backed.
- `pdftotext` was unavailable locally, so PDF validation used Dompdf output, `pdfinfo`, rendered PNG pages, and visual inspection.
- Phase 2B does not add public proposal acceptance, public comments, client proposal uploads, or PDF redesign beyond resilience and balance adjustments.
