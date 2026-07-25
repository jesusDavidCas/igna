# Phase 2A - Proposal Administration and Usability Result

## Phase 1 Closure

- Closed Phase 1 from `fix/ticket-workflow-integrity`.
- Original Phase 1 base commit: `6b49f5c`.
- Local Phase 1 checkpoint commit: `1824b89 feat: harden ticket workflow and document exchange`.
- Created local release branch: `release/igna-workflow-proposals-2026q3`.
- Created and switched to local Phase 2 branch: `feat/proposal-admin-information-architecture`.
- No push, deploy, merge, reset, stash, clean, rebase, amend, or dependency update was performed.

## Existing Proposal Workflow Map

- Admin index: `GET /admin/proposals` -> `Admin\ProposalController@index`.
- Admin create: `GET /admin/proposals/create` -> `Admin\ProposalController@create`.
- Admin store: `POST /admin/proposals` -> `Admin\ProposalController@store`.
- Admin show: `GET /admin/proposals/{proposal}` -> `Admin\ProposalController@show`.
- Admin edit: `GET /admin/proposals/{proposal}/edit` -> `Admin\ProposalController@edit`.
- Admin update: `PUT/PATCH /admin/proposals/{proposal}` -> `Admin\ProposalController@update`.
- Admin PDF: `GET /admin/proposals/{proposal}/pdf` -> `Admin\ProposalController@pdf`.
- Public proposal view remains routed outside the admin proposal path and was not redesigned.

## Final Information Architecture

The admin proposal create/edit form now uses this order:

1. Proposal identity and status.
2. Client information.
3. General proposal documents.
4. Scope and deliverables.
5. Cost items.
6. Payment schedule and totals.
7. Signer and publication actions.

The form keeps the existing proposal fields, service-template selection, item calculations, payment rows, tax handling, signer fields, and submit behavior.

## Proposal Documents

Phase 2A initially added a narrow proposal-document attachment model. During Phase 2B refinement, the business decision changed: IGNA proposals do not require separate general proposal attachments because the generated quote/proposal PDF is the proposal document.

The Phase 2A proposal-document feature was therefore removed in Phase 2B:

- Removed migration: `database/migrations/2026_07_25_000300_create_proposal_documents_table.php`.
- Removed model: `App\Models\ProposalDocument`.
- Removed controller: `Admin\ProposalDocumentController`.
- Removed request: `Admin\ProposalDocumentRequest`.
- Removed upload/download/delete routes and UI.
- Confirmed the local `proposal_documents` table was not active after removal.

## Sorting

The proposal index now:

- Displays `proposals.created_at`.
- Defaults to newest-first sorting.
- Supports oldest-first sorting.
- Preserves search, status filters, and pagination query parameters.
- Normalizes unsupported sort and direction input instead of passing arbitrary SQL order values.

## Terminology

Visible administrator-facing proposal copy now uses Client/Cliente terminology. Legacy technical field names such as `prospect_name`, `prospect_email`, and `prospect_phone` remain because the requested change was product terminology, not a destructive database rename.

## Validation Behavior

The create/edit form now renders:

- A translated validation summary at the top.
- Inline field errors.
- `aria-invalid` and error descriptions for invalid controls.
- Retained old input for identity, client/manual-client, scope, cost rows, payment rows, dates, signer, and notes.
- A first-error target and browser script that scrolls/focuses the first invalid field.

## Files Changed For Phase 2A

- `app/Http/Controllers/Admin/ProposalController.php`
- `app/Http/Requests/Admin/ProposalRequest.php`
- `app/Models/Proposal.php`
- `lang/en/site.php`
- `lang/es/site.php`
- `resources/views/admin/proposals/index.blade.php`
- `resources/views/admin/proposals/partials/form.blade.php`
- `resources/views/admin/proposals/show.blade.php`
- `resources/views/layouts/panel.blade.php`
- `routes/web.php`
- `tests/Feature/ProposalAdministrationUsabilityTest.php`
- `docs/codex/prompts/02a-proposal-admin-information-architecture.md`
- `docs/codex/sessions/02a-proposal-admin-information-architecture-result.md`

## Tests And Verification

Focused tests:

- `php artisan test tests/Feature/ProposalAdministrationUsabilityTest.php`
- `php artisan test tests/Feature/AdminOperationsTest.php`

Migration checks:

- `php artisan migrate`
- `php artisan migrate:rollback --step=1`
- `php artisan migrate`

Browser validation:

- Screenshots saved under `output/ui-review/phase-2a/`.
- Routes checked at 1440x900, 1280x800, 1024x768, 768x900, and 390x844.
- Checked proposal index, create, edit-with-documents, and show-with-documents.
- Representative English create/edit screenshots were also captured.
- Browser `file://` navigation policy blocked a validation-error screenshot from a temporary local HTML render; the validation state remains covered by feature tests and server-render checks.

## Human Visual Test Guide

1. Proposal list creation date:
   - Role: admin.
   - Route: `/admin/proposals`.
   - Spanish label: `Creado`.
   - English label: `Created at`.
   - Control: proposal table or mobile cards.
   - Expected: each proposal shows a localized creation timestamp.
   - Failure indicator: no creation timestamp appears.

2. Newest and oldest sorting:
   - Role: admin.
   - Route: `/admin/proposals`.
   - Spanish labels: `Más recientes primero`, `Más antiguas primero`.
   - English labels: `Newest first`, `Oldest first`.
   - Control: created-at sort link.
   - Expected: clicking toggles chronological order and preserves filters.
   - Failure indicator: query order does not change or filters disappear.

3. Proposal create section order:
   - Role: admin.
   - Route: `/admin/proposals/create`.
   - Spanish labels: `Información de la propuesta`, `Información del cliente`, `Documentos generales de la propuesta`.
   - English labels: `Proposal information`, `Client information`, `General proposal documents`.
   - Control: page scroll.
   - Expected: sections appear in the required seven-step order.
   - Failure indicator: documents appear below scope/costs or fields are missing.

4. Proposal edit section order:
   - Role: admin.
   - Route: `/admin/proposals/{proposal}/edit`.
   - Spanish and English labels match the create route.
   - Control: page scroll.
   - Expected: same seven-step sequence, with document upload enabled.
   - Failure indicator: missing document section or changed calculation controls.

5. General proposal documents upload:
   - Role: admin.
   - Route: `/admin/proposals/{proposal}/edit`.
   - Spanish label: `Subir documento de propuesta`.
   - English label: `Upload proposal document`.
   - Control: title, category, file input, upload button.
   - Expected: valid file appears in the document card list.
   - Failure indicator: upload changes proposal totals/status or exposes a public URL.

6. Proposal document download:
   - Role: admin.
   - Route: `/admin/proposals/{proposal}/edit` or `/admin/proposals/{proposal}`.
   - Spanish label: `Descargar`.
   - English label: `Download`.
   - Control: document-card download link.
   - Expected: authorized download returns the stored file with the sanitized original name.
   - Failure indicator: unauthorized users can download or missing files return a server error.

7. Proposal document deletion:
   - Role: admin.
   - Route: `/admin/proposals/{proposal}/edit`.
   - Spanish label: `Eliminar`.
   - English label: `Delete`.
   - Control: delete button.
   - Expected: document row and private file are removed.
   - Failure indicator: cross-proposal delete works or file remains listed.

8. Client terminology:
   - Role: admin.
   - Routes: `/admin/proposals`, `/admin/proposals/create`, `/admin/proposals/{proposal}/edit`, `/admin/proposals/{proposal}`.
   - Spanish label: `Cliente`.
   - English label: `Client`.
   - Control: visible page copy and validation messages.
   - Expected: affected UI uses Client/Cliente, not Prospect/Prospecto.
   - Failure indicator: user-facing Prospect/Prospecto copy appears.

9. Validation summary and inline errors:
   - Role: admin.
   - Route: `/admin/proposals/create`.
   - Spanish label: `Corrige los siguientes campos`.
   - English label: `Correct the following fields`.
   - Control: submit an invalid form.
   - Expected: top summary appears, inline errors appear, input remains populated, and focus moves to the first invalid field.
   - Failure indicator: valid user input disappears or only a generic error appears.

10. Desktop, tablet, and mobile layout:
    - Role: admin.
    - Routes: proposal index, create, edit, and show.
    - Widths: 1440x900, 1280x800, 1024x768, 768x900, 390x844.
    - Control: resize browser.
    - Expected: no horizontal page overflow, no clipped fields, and document cards remain readable.
    - Failure indicator: body-level horizontal scrolling, clipped text, or unusable document actions.

## Remaining Limitations

- Phase 2A intentionally did not add public proposal acceptance, public comments, client proposal uploads, PDF redesign, restricted rich text editing, integer-quantity migrations, or multi-template appending. Restricted rich text, repeatable template appending, and PDF resilience were later handled in Phase 2B.
- General proposal documents are no longer retained after Phase 2B.
- The validation-error screenshot could not be captured through the Browser plugin from a temporary local file because that plugin blocks `file://` navigation. The rendered validation state is covered by automated feature tests.

## Database Impact

- Adds one reversible proposal document table.
- Existing proposal rows and proposal calculation columns are preserved.
- The migration uses Laravel schema primitives compatible with SQLite and MySQL.

## Security Notes

- Proposal documents are admin-only.
- Routes verify that the document belongs to the proposal route parameter.
- Stored filenames are generated.
- Original names are sanitized for display and download.
- Dangerous file types are rejected by request validation.
- Missing storage objects fail with a 404.
