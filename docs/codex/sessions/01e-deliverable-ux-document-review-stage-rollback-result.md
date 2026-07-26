# Phase 1E Result

## Initial Problem

Phase 1D deliverables were placed below the timeline but used a multi-column card grid. Long filenames and action groups could become cramped. Client-submitted documents had only pending/reviewed/rejected fields, so an administrator download could not be represented separately from explicit review. Stage rollback still needed stricter one-step-only behavior and a safe way to hide abandoned client-facing notes while retaining audit history.

## Layout Before And After

Before: Deliverables rendered as narrow responsive columns.

After: Deliverables render below the project timeline as vertically stacked full-width sections. Each section contains a header, status badge, description, upload area, and full-width document rows. File rows use document information, status, and action zones with wrapping controls and long-name protection.

## Document-Status Lifecycle

Client-originated ticket files use:

- `pending_review`: received, not yet downloaded by an authorized administrator.
- `downloaded`: first successful administrator download occurred.
- `reviewed`: administrator explicitly marked reviewed.
- `rejected`: administrator explicitly rejected the document, with optional reason.

Downloaded is intentionally not described as reviewed.

## Download-Status Semantics

Only a successful administrator download records `first_admin_downloaded_at` and `first_admin_downloaded_by_user_id`. Listing the admin page, missing storage, unauthorized access, client download, and tracking download do not change status. Reviewed and rejected documents are not downgraded by later downloads.

## Stage Movement Rules

Forward movement occurs only by completing the current stage. Completing stage N marks N completed and activates N + 1. Future-stage completion is rejected.

Rollback occurs only by reopening the immediately previous completed stage. From stage 3, stage 2 can be reopened; stage 1 cannot be reopened until stage 2 is current and then rolled back in a separate action.

## Rollback Data Semantics

Rollback updates happen inside `TicketLifecycleService` transactions using row locks. The abandoned current event becomes pending, its current timestamps are reset, and its client-facing notes are superseded. The target previous event becomes the single current event, its completed timestamp is cleared, and its attempt number increments.

## Message Superseding Design

`ticket_stage_events` now has superseding metadata. Client and public timeline rendering hides notes on superseded events. Administrator timeline rendering keeps the notes visible with a Previous execution archived marker and audit details.

`ticket_stage_audits` stores completion and rollback snapshots so audit history is not represented only as free-text notes and is not physically deleted.

## Migration Decision

Migration created:

`database/migrations/2026_07_25_000200_add_review_download_and_stage_rollback_metadata.php`

It adds document download/rejection fields, stage superseding metadata, and the `ticket_stage_audits` table. The migration is additive, reversible, and was applied/rolled back/reapplied against local SQLite only.

## Existing-Data Diagnostic

Read-only local diagnostic command:

```bash
php artisan tinker --execute='/* checks current event count, current-stage mismatch, and later completed stages */'
```

Local result:

- `current_count_issues`: 0
- `current_stage_mismatches`: 0
- `later_completed_issues`: 0

Production repair plan for later deployment: run the same read-only diagnostic first, export affected ticket IDs for review, then apply a reviewed non-destructive repair command that creates audit rows, sets exactly one current event, and preserves historical notes instead of deleting them.

## Exact Routes

- `GET admin/tickets/{ticket}/files/{file}/download`: records first admin download for client-submitted files after successful access/download.
- `PATCH admin/tickets/{ticket}/files/{file}/review`: marks a client-submitted file reviewed.
- `PATCH admin/tickets/{ticket}/files/{file}/reject`: marks a client-submitted file rejected.
- `PUT admin/tickets/{ticket}/stages/{event}/complete`: advances exactly one stage when the event is current.
- `PUT admin/tickets/{ticket}/stages/{event}/reopen`: reopens only the immediately previous completed stage.
- Existing client/tracking download and upload routes are preserved.

## Tests

Focused tests cover:

- stacked full-width deliverables;
- long filename/action wrapping structure;
- document pending/downloaded/reviewed/rejected lifecycle;
- first admin download metadata;
- missing storage and unauthorized/non-client-file non-transitions;
- strict one-step rollback;
- client/public hiding of superseded messages;
- admin audit visibility;
- UI button availability by stage.

## Local QA

Final verification passed:

- `php artisan migrate && php artisan migrate:rollback --step=1 && php artisan migrate`: passed.
- `php artisan test tests/Feature/TicketLayoutDocumentExchangeTest.php`: 11 tests, 158 assertions.
- `php artisan test tests/Feature/TicketWorkflowIntegrityTest.php`: 14 tests, 149 assertions.
- `php artisan test`: 78 tests, 638 assertions.
- `npm run build`: passed.
- `git diff --check`: passed.

## Human Visual Test Guide

1. Deliverables: admin, `/admin/tickets/{ticket}`, Spanish `Entregables`, English `Deliverables`; expect one full-width deliverable section per row below the project timeline.
2. Long filename: upload a deliverable PDF with a very long name; expected document row wraps inside the card, with Download file/Descargar archivo, Hide from client/Ocultar al cliente, Delete file/Eliminar archivo still usable.
3. Client pending: client uploads a PDF from My Services -> ticket, `Send a document` / `Enviar un documento`; expected `Pending review` / `Pendiente de revisión`.
4. Admin download: admin downloads that document; expected client sees `Downloaded by IGNA` / `Descargado por IGNA`, not reviewed.
5. Mark reviewed: admin clicks `Mark reviewed` / `Marcar revisado`; expected client sees `Reviewed` / `Revisado`.
6. Reject document: upload another client document, admin enters rejection reason and clicks `Reject document` / `Rechazar documento`; expected client sees `Rejected` / `Rechazado` and the safe rejection text.
7. Stage forward: admin completes stage 1 then stage 2; expected current stage advances exactly one at a time.
8. Direct rollback rejection: from stage 3, try to reopen stage 1 directly by manipulated request; expected 422.
9. One-step rollback: from stage 3, click `Reopen previous stage` / `Reabrir etapa anterior`; expected stage 2 becomes current and stage 3 becomes pending.
10. Superseded timeline: confirm abandoned stage-3 notes disappear from client/tracking timeline but remain visible in admin audit history.
11. Second rollback: after stage 2 is current, reopen stage 1 in a separate action; expected one current event.
12. Commercial separation: review/reject a payment receipt; expected no proposal acceptance, invoice payment, ticket close, or stage completion.

## Remaining Limitations

- No browser screenshot test was added; feature tests assert rendered structure and behavior.
- Rollback audit is represented in the admin timeline details, not a separate dedicated audit screen.
- No production repair command was executed or added; only a documented read-only diagnostic and repair plan are provided.
