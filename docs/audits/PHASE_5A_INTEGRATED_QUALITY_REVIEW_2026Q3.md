# IGNA Studio Phase 5A Integrated Quality Review - 2026 Q3

## Review Identity

- Review branch: `qa/phase-5a-integrated-release-candidate`
- Phase 5A.5 checkpoint: `3204cfd4bfaab2a889247e94981f631965fd92a2`
- Phase 5A.4 base: `5934b80baa04bcec259b16bf6c826a24f3d91a3a`
- Included ancestry:
  - `63811c6` - rasterized credential protection
  - `8e128ba` - ticket upload confirmations and notifications
  - `10c4ad1` - structured bilingual service administration
  - `5934b80` - grouped public services by business category
  - `3204cfd` - reusable proposal cost-template catalogue

## Integrated Architecture Map

### Phase 5A.1 - Credential Protection

- Routes: `team.credentials.show`, `team.credentials.preview`, `team.credentials.file`, `admin.team.credentials.store`, `admin.team.credentials.regenerate`, `admin.team.credentials.destroy`.
- Middleware: public signed route middleware plus throttling for credential view/file/preview; admin routes behind authenticated active admin middleware.
- Authorization: public credential access requires signed URL, active team member, public credential, and credential/team-member match; admin credential mutations require admin access.
- Controllers: `Public\TeamCredentialController`, `Admin\TeamCredentialController`.
- Form request: `Admin\TeamCredentialRequest`.
- Service: `Credentials\CredentialPreviewRenderer`.
- Models and relationships: `TeamMember` has many `TeamCredential`; `TeamCredential` has many `TeamCredentialView`.
- Migration: `2026_07_29_000100_add_protected_derivative_fields_to_team_credentials`.
- Views: `resources/views/public/team/show.blade.php`, `resources/views/public/team/credential.blade.php`, admin team edit views.
- Output: original stored privately; protected derivative served as PDF; preview pages served as JPEG.
- Tests: `TeamCredentialProtectionTest`, credential coverage in `AdminOperationsTest` and `PublicPlatformTest`.

### Phase 5A.2 - Ticket Document Upload Confirmations

- Routes: `tracking.documents.store`, `client.tickets.documents.store`.
- Middleware: tracking flow uses signed context and public constraints; client route uses authenticated active client middleware.
- Authorization: tracking upload requires valid ticket context; authenticated client upload requires owned ticket; cross-client upload/download is denied.
- Controller: `TicketClientDocumentController`.
- Request: ticket document upload request validation in the controller path and related file request classes.
- Event/listener: `TicketClientDocumentUploaded` implements `ShouldDispatchAfterCommit`; `SendTicketClientDocumentUploadNotifications` handles client and administrator notifications.
- Service: `ProjectNotificationService` resolves recipients and locale.
- Models: `Ticket`, `TicketFile`, `TicketDeliverable`, `User`.
- Email output: `TicketDocumentUploadedClientMail`, `TicketDocumentUploadedAdminMail`.
- Tests: `TicketLayoutDocumentExchangeTest`, mail locale coverage in `TicketWorkflowIntegrityTest`.

### Phase 5A.3 - Service Administration Structure

- Routes: `admin.services.*`, `admin.services.translate`, `admin.services.stages.*`, `admin.services.stages.translate`.
- Middleware: authenticated active admin middleware.
- Authorization: administrators and superadministrators can manage services; clients and guests cannot.
- Controller: `Admin\ServiceController`, with stage mutation paths.
- Form request: `Admin\ServiceRequest`.
- Services: `ServiceContentTranslator`, `ServiceDeliverableNormalizer`.
- Models and relationships: `Service` has many `ServiceDeliverable`, `ServiceStage`, and `Ticket`.
- Migration: `2026_07_30_000100_add_structured_bilingual_service_content`.
- Views: admin service index/form/edit views; public service rendering through `LandingController` and public home view.
- Tests: `ServiceAdministrationStructuredContentTest`, related service coverage in `AdminOperationsTest`.

### Phase 5A.4 - Public Service Taxonomy

- Routes: public `requests.store`; admin service routes for category assignment.
- Middleware: public request route uses web protections; admin service mutations require authenticated active admin.
- Authorization: public service selection accepts active catalog services or the explicit "other" flow; inactive services and optgroup headings are rejected.
- Controllers: `Public\LandingController`, `Public\ServiceRequestController`, `Admin\ServiceController`.
- Form request: `Public\StoreServiceRequestRequest`, `Admin\ServiceRequest`.
- Service: `Services\PublicServiceTaxonomy`.
- Models: `Service`, `Ticket`.
- Migration: `2026_07_30_000200_allow_other_public_service_requests`.
- Views: `resources/views/public/home.blade.php`, admin service form.
- Tests: `PublicServiceTaxonomyRequestTest`.

### Phase 5A.5 - Proposal Cost-Template Catalogue

- Routes: `admin.proposal-templates.index`, `create`, `store`, `edit`, `update`, `duplicate`, `status`.
- Middleware: authenticated active admin middleware.
- Authorization: administrators and superadministrators can manage proposal templates, matching the proposal-administration model.
- Controller: `Admin\ProposalServiceTemplateController`.
- Form request: `Admin\ProposalServiceTemplateRequest`.
- Models: `ProposalServiceTemplate` has many ordered `ProposalServiceTemplateItem`; proposal rows are persisted as independent `ProposalItem` snapshots.
- Migration: no new migration; reuses the existing proposal-template schema from `2026_06_01_000001_create_proposal_service_templates_table`.
- Views: admin proposal-template catalogue create/edit/index/form partials; proposal editor partial.
- JavaScript: proposal editor template insertion, copy-count handling, row insertion, totals recalculation.
- PDF output: proposal PDF reads saved proposal rows, not live template rows.
- Tests: `ProposalCostTemplateCatalogueTest`, `ProposalAdministrationUsabilityTest`, `AdminOperationsTest`.

## Database And Migration Review

- New Phase 5A migrations are additive and ordered after prior release migrations.
- Phase 5A.5 introduced no migration and reused the existing proposal template tables.
- SQLite rehearsal was performed with a disposable database: full migrate, rollback of the Phase 5A service batch, reapply, and status check passed.
- MySQL compatibility was inspected at the migration level. New fields are nullable or have safe defaults. The "other request" migration intentionally blocks rollback when null-service tickets exist, because data cannot be safely coerced back into a non-null catalog service.
- Model casts and fillable lists match new fields for services, tickets, team credentials, and proposal templates.
- Relationship boundaries remain explicit: public service content, proposal templates, proposal snapshots, and ticket deliverables are separate data paths.

## Grouped Selector Investigation

- Local database has active `digital` and `engineering` services.
- Browser check at `http://127.0.0.1:8000/` rendered the grouped selector with persisted services.
- Spanish session labels rendered as `Tecnologia` with accent in the UI text and `Ingenieria de Infraestructura` with accent in the UI text; "Otra solicitud / No estoy seguro" remained available.
- The previous report that grouped labels were absent was not reproduced. The likely cause was language/accent mismatch or stale local browser state, not a code defect.
- Source tests also verify English category labels, Spanish category labels, inactive filtering, fallback to `other`, and selected-service persistence after validation errors.

## Authorization Matrix

| Feature | Guest | Client | Administrator | Superadministrator |
| --- | --- | --- | --- | --- |
| Credential admin | Denied | Denied | Allowed | Allowed |
| Signed credential view/download | Allowed only with valid signed public URL | Allowed only with valid signed public URL | Allowed only with valid signed public URL | Allowed only with valid signed public URL |
| Public tracking upload | Allowed with valid tracking context | Allowed with valid tracking context | Allowed with valid tracking context | Allowed with valid tracking context |
| Authenticated client upload | Denied | Allowed only for owned ticket | Denied through client route | Denied through client route |
| Service administration | Denied | Denied | Allowed | Allowed |
| Service category assignment | Denied | Denied | Allowed | Allowed |
| Proposal-template catalogue | Denied | Denied | Allowed | Allowed |
| Proposal editing | Denied | Denied | Allowed | Allowed |
| Public request submission | Allowed | Allowed | Allowed | Allowed |

The Phase 5A.5 decision to allow administrators and superadministrators to manage proposal templates matches existing proposal and service administration access patterns.

## Localization Audit

- `lang/en/site.php` and `lang/es/site.php` have matching flattened keys.
- User-facing Phase 5A messages are translation-key based.
- Service labels, category labels, ticket upload confirmations, administrator notifications, client notifications, and proposal-template labels resolve through the active or recipient locale.
- Legacy template and service fallback behavior is read-only and does not overwrite stored content.
- Existing legacy proposal-template records remain readable; editing requires completing required bilingual catalogue fields.

## Cross-Module Independence

- Public service titles, categories, deliverables, and workflow stages are independent from proposal-template titles and cost rows.
- Proposal-template edits do not mutate saved proposal items or historical proposal PDFs.
- Proposal-template deactivation hides templates from the proposal editor but does not hide public services.
- Service deactivation and category edits do not remove proposal templates.
- Ticket workflow stages are not affected by service workflow-stage editing.
- Tests explicitly cover service/template independence and proposal snapshot immutability.

## Event And Mail Transaction Review

- `TicketClientDocumentUploaded` dispatches after commit.
- Listener reloads the persisted ticket/file, verifies the file belongs to the ticket, and ignores non-client-submitted files.
- `ProjectNotificationService` sends one client message and deduplicated responsible-administrator messages.
- Inactive administrator recipients are excluded; support fallback is used only when responsible admins are unavailable.
- Mail failures are caught and reported without rolling back the upload.
- File attachments and private storage paths are not sent in the document-upload notification mail path.
- Current handling is synchronous after commit; no queue system was introduced in this phase.

## Credential Security Review

- Original credentials remain on private local storage.
- Public routes serve only protected derivative PDFs or JPEG previews.
- Missing derivative fails closed.
- Regeneration failure preserves a previous valid derivative when one exists.
- Temporary raster files are removed by the renderer.
- Download responses set private/no-store cache headers, noindex robots headers, attachment disposition, and `nosniff`.
- Private storage paths were not found in credential HTTP responses covered by tests.
- Superseding Phase 5A.8/Hostinger note: PHP GD, Ghostscript `/usr/bin/gs` 9.54.0, Imagick diagnostics, and `proc_open`/`proc_get_status`/`proc_close` are verified. Poppler `pdftoppm` remains preferred when available; direct Ghostscript execution is the supported fallback.

## Code Quality Findings

- No broad refactor was performed.
- No orphaned Phase 5A controller, unbound route, duplicate route name, duplicate DOM ID, or new public admin route was found.
- The only transient test failure was caused by parallel execution of separate PHP test processes that shared fake storage roots; serial rerun passed.
- `ProposalServiceTemplateController` uses transactions for create/update/duplicate and keeps proposal row snapshotting outside the live template tables.
- No evidence of new private-data logging, unbounded public data exposure, or unsafe mass assignment was found.

## Route Review

- `php artisan route:list --except-vendor` reported 93 routes.
- New destructive routes use POST, PATCH, PUT, or DELETE, not GET.
- New admin routes are grouped under admin middleware.
- Public credential routes use signed middleware and throttling.
- Public tracking and client upload routes remain distinct.

## Browser Results

- Local browser session reached the public homepage, admin proposal-template catalogue, proposal creation form, service administration, and team administration.
- Public grouped selector rendered active persisted local services under category headings and included the explicit "Other" option.
- Proposal editor rendered the template selector, copy-count control, add-template button, management link, and no duplicate DOM IDs.
- Browser console error checks returned no errors for the checked public and admin pages.
- Browser upload, download, PDF visual inspection, and mail-preview review remain human QA steps because they require choosing local fixtures and possibly transmitting local form data.

## Automated Test Results

- `ProposalCostTemplateCatalogueTest`: 7 passed, 56 assertions.
- `TeamCredentialProtectionTest`: 4 passed, 28 assertions.
- `ServiceAdministrationStructuredContentTest`: 9 passed, 54 assertions.
- `PublicServiceTaxonomyRequestTest`: 9 passed, 62 assertions.
- `TicketLayoutDocumentExchangeTest`: 14 passed, 208 assertions.
- Serial integrated regression band: 115 passed, 1068 assertions.
- Full suite: 133 passed, 1121 assertions.
- No skipped, risky, or failed tests were reported in the final full run.

## Build, Audit, And Route Verification

- `composer validate`: passed.
- `composer audit --locked`: no advisories.
- `npm audit --omit=dev`: zero vulnerabilities.
- `npm audit`: development-only advisories remain in `postcss`, `shell-quote` via `concurrently`, and `vite`.
- `npm run build`: passed with existing Node `DEP0205` deprecation warning.
- `git diff --check`: passed before documentation changes.
- `php artisan migrate:status`: all migrations are applied in the local database.

## Graphify Results

- Command: `uv tool run --from graphifyy graphify update .`.
- Result: code graph updated.
- Re-extracted files: 4 uncached code files.
- Nodes: 1938.
- Edges: 3358.
- Communities: 243.
- Health notes: 97 percent extracted edges, 3 percent inferred edges, 0 percent ambiguous edges.
- Warning: `skills-lock.json` produced zero nodes; this is an untracked baseline artifact, not application code.
- Graphify output remains uncommitted.

## Confirmed Defects

- No release-blocking Phase 5A.6 integration defect was confirmed.
- The previous grouped-selector browser concern was not reproduced.
- The parallel-test fake-storage race was a local verification-method issue and was resolved by serial rerun.

## Corrections Implemented

- No source-code corrections were required.
- Phase 5A.6 documentation was added for human review and remains unstaged.

## Deferred Improvements

- Human visual QA should complete upload, download, PDF, and mail-preview paths with local non-sensitive fixtures.
- Development-only npm advisories should be handled in a separate dependency-maintenance pass.
- Production credential generation prerequisites have been confirmed on Hostinger for the supported Ghostscript fallback. Poppler remains preferred when available.

## Production Prerequisites

- PHP GD enabled.
- Poppler `pdftoppm` available to the Laravel process, or Ghostscript `/usr/bin/gs` available for the supported fallback.
- `proc_open`, `proc_get_status`, and `proc_close` enabled.
- Writable Laravel storage and cache directories.
- Mail configuration verified in production without exposing credentials.
