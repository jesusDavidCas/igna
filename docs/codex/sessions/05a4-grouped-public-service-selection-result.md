# Phase 5A.4 - Grouped Public Service Selection Result

## Phase 5A.3 Checkpoint

- Phase 5A.2 checkpoint: `8e128bac3474c07db95da6a02c7b773bf3ba423f`
- Phase 5A.3 local checkpoint commit: `10c4ad180917a70223c93b6a6d3d7d0ee3bc946f`
- Phase 5A.3 commit message: `feat: structure bilingual service administration`
- Phase 5A.4 branch created from that commit: `feat/grouped-public-service-selection`

## Existing Request Architecture

- Public request entry point renders through `LandingController` and `resources/views/public/home.blade.php`.
- The public form posts to `ServiceRequestController::store`.
- Validation is handled by `StoreServiceRequestRequest`.
- Public requests create `Ticket` records through `TicketLifecycleService::createFromPublicRequest`.
- Catalog service requests use `tickets.service_id` and initialize service stages and deliverables from the selected service.
- Request emails are sent through `ProjectNotificationService` using `ProjectUpdateMail` and `AdminNewTicketMail`.
- Public tracking, admin tickets, client tickets, dashboards, and email templates display the selected service name.

## Taxonomy Architecture Decision

The implementation reuses the existing `services.business_line` field because it already separates digital/technology work from engineering work and is already validated in service administration.

A new `PublicServiceTaxonomy` service maps existing internal values to public, language-neutral category codes:

- `digital` maps to `technology`
- `engineering` maps to `infrastructure_engineering`
- unknown or legacy values map to `other`

No category table was added because categories do not yet need independent records, activation, ordering, relationships, or administrative CRUD.

## Category Labels And Ordering

Labels are stored in `lang/en/site.php` and `lang/es/site.php`.

English:

- `technology`: Technology
- `infrastructure_engineering`: Infrastructure Engineering
- `other`: Other / I am not sure

Spanish:

- `technology`: Tecnologia
- `infrastructure_engineering`: Ingenieria de Infraestructura
- `other`: Otra solicitud / No estoy seguro

Public category order is deterministic:

1. Technology
2. Infrastructure Engineering
3. Other

Within each category, services are sorted by existing `sort_order`, then localized service name, then database id.

## Service Category Assignment

- Superadministrators continue assigning the underlying business line in service create/edit.
- The admin field is now labeled as the public service category while preserving stored values `digital` and `engineering`.
- Validation remains constrained by the existing service business-line/type/scope rules.
- Updating the category does not rewrite service slugs, proposal templates, deliverables, stages, or historical requests.

## Legacy And Uncategorized Handling

Active services with unexpected legacy `business_line` values are not hidden. They resolve to the public `other` group for safe transitional visibility and human review.

## Grouped Public Selector

The public request form now uses native `<optgroup>` markup:

- Technology group for active technology services.
- Infrastructure Engineering group for active engineering services.
- Empty groups are omitted.
- The explicit "Other / I am not sure" option remains selectable.
- Category headings are not valid submitted values.
- Old input preserves selected catalog service ids and the `other` option after validation errors.

## Other Request Behavior

Selecting `other` creates a ticket without creating a fake service record.

Stored values:

- `service_id`: `null`
- `service_selection`: `other`
- `service_public_category`: `other`

Other requests do not initialize catalog workflow stages or deliverables. They keep the existing free-text project description and send the existing request notifications with the localized "Other / I am not sure" label.

## Request Persistence Compatibility

Catalog service requests still persist the selected service id and initialize stages/deliverables exactly as before.

The ticket model now exposes:

- `hasCatalogService()`
- `serviceDisplayName()`
- `serviceCategoryLabel()`

Public, admin, client, dashboard, tracking, and email displays use the display helper so historical catalog requests and new "Other" requests render safely.

## Schema Impact

Migration `2026_07_30_000200_allow_other_public_service_requests.php`:

- Adds `tickets.service_selection` with default `catalog`.
- Adds nullable `tickets.service_public_category`.
- Relaxes `tickets.service_id` to nullable so an explicit "Other" request does not require a fake service row.
- Preserves existing ticket/service references.
- Reversible down logic restores non-null `service_id` only when no null-service tickets exist.

Local migration apply, rollback, and reapply passed.

MySQL impact:

- The migration changes a foreign-keyed column and adds two small columns on `tickets`.
- Expect brief metadata locks; run during a quiet maintenance window in production.

## Focused Tests

Added `tests/Feature/PublicServiceTaxonomyRequestTest.php`.

Coverage includes:

- English and Spanish category labels.
- Language-neutral category codes.
- Deterministic order.
- Empty group omission.
- Legacy fallback to `other`.
- Superadministrator category assignment.
- Invalid category rejection.
- Preservation of translations, deliverables, and stages.
- Catalog technology and infrastructure request submissions.
- Rejection of category headings and inactive services.
- Other request persistence without fake services.
- Mail/display compatibility for other requests.
- Selected value retention after validation errors.

Focused result:

- `php artisan test --filter=PublicServiceTaxonomyRequestTest`
- Result: 9 passed, 62 assertions.

## Regression Verification

Phase 5A regression band:

- `php artisan test --filter='PublicServiceTaxonomyRequestTest|PublicPlatformTest|ServiceAdministrationStructuredContentTest|TeamCredentialProtectionTest|TicketLayoutDocumentExchangeTest|TicketWorkflowIntegrityTest|ProposalAdministrationUsabilityTest'`
- Result: 71 passed, 727 assertions.

Full suite:

- `php artisan test`
- Result: 126 passed, 1065 assertions.

Additional verification is recorded in the final response for this execution.

Release-style local gates:

- `composer audit --locked`: no security vulnerability advisories found.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm run build`: passed with the existing Node `DEP0205` deprecation warning.
- `git diff --check`: passed.
- `php artisan migrate:status`: Phase 5A.4 migration applied locally as batch 8.
- `git diff --cached --stat`: empty; Phase 5A.4 remains unstaged.

## Browser Validation

Automated local browser validation completed for the public request selector:

- English selector showed `Technology`, `Infrastructure Engineering`, and `Other / I am not sure`.
- Spanish selector showed `Tecnologia`, `Ingenieria de Infraestructura`, and `Otra solicitud / No estoy seguro`.
- Category headings were not selectable values.
- "Other" selection survived an invalid-email validation redirect.
- Desktop and 390px-wide mobile viewport checks reported no horizontal overflow.
- Browser console inspection reported no errors.

Admin service-edit browser validation remains pending for the integrated Phase 5A.3/5A.4 human review because the in-app browser did not have an authenticated local superadministrator session and redirected `/admin/services` to `/login`.

The application should be reviewed at `http://127.0.0.1:8000` with a local superadministrator session.

Do not record credentials, cookies, signed URLs, private request data, or browser state in committed files.

## Integrated Review Guide

Admin review:

1. Open service administration.
2. Edit one Technology service and one Infrastructure Engineering service.
3. Confirm the category field is selected correctly.
4. Save and reload.
5. Confirm bilingual service names, deliverables, and workflow stages remain intact.

Public English review:

1. Open the public request form.
2. Confirm Technology and Infrastructure Engineering headings.
3. Confirm services are under the correct headings.
4. Confirm "Other / I am not sure".
5. Trigger validation and confirm selected value retention.
6. Submit a catalog request and an "Other" request using local synthetic data.

Public Spanish review:

1. Switch to Spanish.
2. Confirm Tecnologia, Ingenieria de Infraestructura, and Otra solicitud / No estoy seguro.
3. Confirm localized service names.
4. Trigger validation and confirm selected value retention.
5. Submit an infrastructure request using local synthetic data.

Responsive review:

1. Review desktop and narrow/mobile widths.
2. Confirm optgroup headings remain readable.
3. Confirm keyboard selection works.
4. Confirm no horizontal overflow appears in the form.

## Files Changed

- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Controllers/Public/LandingController.php`
- `app/Http/Requests/Public/StoreServiceRequestRequest.php`
- `app/Models/Service.php`
- `app/Models/Ticket.php`
- `app/Services/Services/PublicServiceTaxonomy.php`
- `app/Services/Tickets/TicketLifecycleService.php`
- `database/migrations/2026_07_30_000200_allow_other_public_service_requests.php`
- `lang/en/site.php`
- `lang/es/site.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/services/partials/form.blade.php`
- `resources/views/admin/tickets/index.blade.php`
- `resources/views/admin/tickets/show.blade.php`
- `resources/views/client/dashboard.blade.php`
- `resources/views/client/tickets/show.blade.php`
- `resources/views/emails/admin-new-ticket.blade.php`
- `resources/views/emails/project-update.blade.php`
- `resources/views/emails/ticket-document-uploaded-admin.blade.php`
- `resources/views/emails/ticket-document-uploaded-client.blade.php`
- `resources/views/public/home.blade.php`
- `resources/views/public/tracking.blade.php`
- `tests/Feature/PublicServiceTaxonomyRequestTest.php`
- `docs/codex/prompts/05a4-grouped-public-service-selection.md`
- `docs/codex/sessions/05a4-grouped-public-service-selection-result.md`

## Remaining Limitations

- Browser validation remains pending for the integrated Phase 5A.3/5A.4 human review.
- Category administration intentionally remains a small service edit field, not a standalone taxonomy builder.
- Existing `business_line` values remain the stored source; public category codes are derived from that field.
- Other requests intentionally do not receive catalog workflow stages or deliverables until a human classifies them.
- Phase 5A.4 changes are intentionally unstaged and uncommitted.
