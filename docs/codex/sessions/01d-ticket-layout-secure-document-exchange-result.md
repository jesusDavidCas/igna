# Phase 1D Result

## Initial Layout

Before Phase 1D, the administrator ticket page had ticket summary and timeline in the main column, while assignment, stage completion, upload form, deliverables, and general files were stacked in a narrow sidebar. Deliverable files and general files were presented together inside the same sidebar card.

## Final Layout

The administrator page now uses:

- Main content: ticket summary, project timeline, wide deliverables section.
- Right sidebar: assign client, general project files, stage completion, previous-stage correction when applicable, client-submitted documents.

Deliverables are rendered as clean white cards in a responsive grid with status, description, deliverable-specific upload controls, visibility controls, downloads, and deletion.

## Route Map

- `POST /request`, route `requests.store`: creates ticket and optional initial client-submitted document.
- `POST /portal/tickets/{ticket}/documents`, route `client.tickets.documents.store`: authenticated client document upload.
- `POST /tracking/tickets/{ticket}/documents`, route `tracking.documents.store`: signed public tracking document upload with `throttle:ticket-document-upload`.
- Existing downloads remain `admin.tickets.files.download`, `client.tickets.files.download`, and `tracking.files.download`.
- Existing admin uploads remain `admin.tickets.files.store`.

## Data-Model Decision

`ticket_files.deliverable_type` remains the category/source-of-truth for file category. A narrow additive migration added:

- `upload_source`: `admin`, `initial_request`, `authenticated_client`, `public_tracking`.
- `review_status`: `reviewed`, `pending_review`, `rejected`.
- `submitted_context_hash`: signed/public email context hash.
- `reviewed_by_user_id`, `reviewed_at`: future review audit fields.

Legacy records default to `upload_source=admin` and `review_status=reviewed`.

## Migration Decision

Migration created: `database/migrations/2026_07_25_000100_add_client_document_metadata_to_ticket_files.php`.

It is additive, preserves existing `ticket_files`, supports SQLite/MySQL-compatible column types, and has reversible down logic. Local apply, rollback, and reapply were verified.

## Administrator File Policy

Administrator files retain the existing 20 MB policy and existing allowlist through `TicketFileUploadRequest`. General project files use categories: proposal, invoice, bank information, payment instructions, agreement, project document, other. They are internal by default and become downloadable by clients/tracking only when explicitly marked client-visible.

## Client File Policy

Client-originated uploads are limited to PDF, JPG/JPEG, and PNG, maximum 2 MB, from the initial public request, authenticated client portal, and validated public tracking page.

## Security Inspection Layers

`ClientDocumentSecurityService` performs centralized checks:

- successful PHP upload;
- 2 MB server-side size limit;
- extension allowlist;
- content-derived MIME detection;
- extension/MIME/signature agreement;
- double-extension and null-byte filename rejection;
- rejection of HTML, SVG, scripts, executables, archives, Office files, invalid PDFs, and malformed images;
- random storage filename;
- sanitized original display filename;
- private `local` disk storage under `client-documents/`;
- optional GD image re-encoding when available;
- attachment downloads with `X-Content-Type-Options: nosniff`;
- rejection logging without file contents or sensitive client data.

## Scanner Availability

No antivirus, qpdf, or CDR service was found or added. PDFs are validated for MIME/signature/basic structure and stored privately as pending review. This does not prove malware-free status.

## Quarantine Behavior

Client-submitted files start as `pending_review`, are not client-visible shared files, and are only downloadable through authorized admin/client/tracking routes by the submitting context.

## Signed Tracking Workflow

After a valid ticket-code/email lookup, the tracking page receives a temporary signed upload action bound to ticket ID and `email_hash`. The upload route verifies signature, expiry, ticket binding, email hash, category, file validation, and rate limit. Limiter: max 5 attempts per 15 minutes per ticket/context/IP.

## Proposal/Payment Separation

Proposal PDFs can be shared as admin general project files with category `proposal`. Payment receipts are client-submitted documents with category `payment_receipt`. Uploading or downloading either does not accept proposals, mark invoices paid, close tickets, or complete stages.

Future explicit proposal workflow remains out of scope and should eventually record proposal ID, immutable version/snapshot, recipient context, decision, timestamp, optional comment, and audit evidence.

## Tests

Focused Phase 1D tests cover layout order, wide deliverables, admin proposal uploads, client PDF/JPEG/PNG uploads, unsafe file rejection, wrong-client denial, signed tracking upload, expired/unsigned/wrong-hash rejection, throttling, initial request attachment policy, secure storage headers, stage invariants, and demo seeder no-mail regression.

## Local QA

Local migration apply/rollback/reapply passed. Final verification passed:

- `php artisan test tests/Feature/TicketLayoutDocumentExchangeTest.php`: 8 tests, 105 assertions.
- `php artisan test`: 73 tests, 537 assertions.
- `npm run build`: passed.
- `git diff --check`: passed.

## Human Visual Test Guide

1. Admin card order: admin, `/admin/tickets/{ticket}`, Spanish `Asignar cliente`, `Archivos generales del proyecto`, `Finalización de etapas`; English `Assign client`, `General project files`, `Stage completion`; expected in that order.
2. Wide deliverables: admin, same page, Spanish `Entregables`, English `Deliverables`; expected below project timeline in a wide responsive grid.
3. Administrator proposal upload: admin, General project files, category Proposal/Propuesta, PDF under 20 MB; expected private unless Available to client is checked.
4. Administrator bank-information upload: admin, General project files, category Bank information/Información bancaria; expected internal by default.
5. Client PDF upload: client, My Services -> ticket, Send a document/Enviar un documento, valid PDF under 2 MB; expected Pending review/Pendiente de revisión under Documents you sent/Documentos que enviaste.
6. Client image upload: same, valid JPG/PNG under 2 MB; expected pending review.
7. Client file larger than 2 MB: same, oversized PDF/image; expected validation error.
8. Client DOCX rejection: same, DOCX file; expected validation error.
9. Documents you sent: client/tracking ticket detail; expected client submissions separated from Files shared with you/Archivos compartidos contigo.
10. Public tracking upload: visitor, `/tracking`, validate ticket code and email, then upload through Send a document; expected success and pending review.
11. Invalid signed upload: manually remove/expire signature from tracking upload action; expected 403.
12. Wrong-client upload: login as another client and POST to another ticket; expected 404.
13. Stage remains unchanged: compare current stage before/after any upload; expected no stage movement.
14. Payment receipt is not acceptance: upload Payment receipt/Comprobante de pago; expected pending review only, no proposal/payment/ticket status change.

## Exact Files Changed

- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Controllers/Client/TicketController.php`
- `app/Http/Controllers/Public/ServiceRequestController.php`
- `app/Http/Controllers/Public/TicketTrackingController.php`
- `app/Http/Controllers/TicketClientDocumentController.php`
- `app/Http/Controllers/TicketFileDownloadController.php`
- `app/Http/Requests/Public/StoreServiceRequestRequest.php`
- `app/Http/Requests/TicketClientDocumentUploadRequest.php`
- `app/Mail/AdminNewTicketMail.php`
- `app/Models/TicketFile.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Files/GoogleDriveFileManager.php`
- `app/Services/Notifications/ProjectNotificationService.php`
- `app/Services/Tickets/ClientDocumentSecurityService.php`
- `app/Services/Tickets/TicketFileAccessService.php`
- `bootstrap/app.php`
- `database/migrations/2026_07_25_000100_add_client_document_metadata_to_ticket_files.php`
- `lang/en/site.php`
- `lang/es/site.php`
- `resources/views/admin/tickets/show.blade.php`
- `resources/views/client/tickets/show.blade.php`
- `resources/views/emails/admin-new-ticket.blade.php`
- `resources/views/partials/ticket-file-card.blade.php`
- `resources/views/public/home.blade.php`
- `resources/views/public/tracking.blade.php`
- `routes/web.php`
- `tests/Feature/TicketLayoutDocumentExchangeTest.php`
- `docs/codex/prompts/01d-ticket-layout-secure-document-exchange.md`
- `docs/codex/sessions/01d-ticket-layout-secure-document-exchange-result.md`

## Remaining Risks

- No malware scanner/CDR is integrated; files remain pending review and attachment-only.
- Image re-encoding depends on GD availability. Without GD, images are still privately stored and served as attachments after validation.
- Review/reject UI is not implemented in Phase 1D, though schema fields now support it.
