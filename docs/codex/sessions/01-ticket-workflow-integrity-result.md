# Goal

Phase 1 ticket workflow integrity was implemented across email locale resolution, stage-transition rules, and client file visibility/access.

# Starting branch and commit

- Branch: `fix/ticket-workflow-integrity`
- Starting commit: `6b49f5c`
- Initial safety gate passed for root, branch, commit, whitespace, and conflict-marker scan.

# Starting worktree inventory

Accepted baseline artifacts at start:

- `.agents/`
- `.graphifyignore`
- `.playwright-cli/`
- `AGENTS.md`
- `docs/AI_ARCHITECTURE_GRAPH.md`
- `docs/LANDING_UI_SYSTEM.md`
- `docs/audits/`
- `graphify-out/`
- `graphify-query-smoke-test.txt`
- `output/`
- `resources/images/`
- `skills-lock.json`
- `tests/Feature/FunctionalBoundaryTest.php`
- known `.env.backup*` local setup artifacts, not inspected

# Graphify evidence

- `graphify-out/graph.json` existed and was used as the baseline.
- `graphify` CLI was not on PATH, so the graph was inspected with a read-only Python traversal of `graphify-out/graph.json`.
- GRAPH-EXTRACTED nodes included `ProjectNotificationService`, `ProjectUpdateMail`, `AdminNewTicketMail`, `TicketLifecycleService`, `TicketController`, `TicketFileDownloadController`, `Ticket`, `TicketFile`, and `TicketStageEvent`.
- SOURCE-VERIFIED conclusions were confirmed in the related source files before editing.
- Graphify was not regenerated.

# Route map

Public request submission:

- User-facing section: Public home request form
- HTTP: `POST /request`
- Route: `requests.store`
- Middleware: `web`, `throttle:10,1`
- Definition: `routes/web.php:53`
- Controller: `App\Http\Controllers\Public\ServiceRequestController::store`
- Service: `App\Services\Tickets\TicketLifecycleService::createFromPublicRequest`
- Tables: `tickets`, `ticket_stage_events`, `ticket_deliverables`, `ticket_files`
- Tests: `tests/Feature/PublicPlatformTest.php`, `tests/Feature/TicketWorkflowIntegrityTest.php`

Ticket admin:

- User-facing section: Admin -> Tickets
- HTTP: `GET /admin/tickets`, `GET /admin/tickets/{ticket}`
- Routes: `admin.tickets.index`, `admin.tickets.show`
- Middleware: `web`, `auth`, `role:super_admin,admin`
- Definition: `routes/web.php:109`, `routes/web.php:110`
- Controller: `App\Http\Controllers\Admin\TicketController::index`, `show`
- Tests: `tests/Feature/AdminOperationsTest.php`

Client assignment:

- HTTP: `PUT /admin/tickets/{ticket}/client`
- Route: `admin.tickets.client.update`
- Definition: `routes/web.php:111`
- Controller: `App\Http\Controllers\Admin\TicketController::updateClient`
- Tables: `tickets.client_user_id`

Stage selection/update:

- HTTP: `PUT /admin/tickets/{ticket}/stage`
- Route: `admin.tickets.stage.update`
- Definition: `routes/web.php:112`
- Controller: `App\Http\Controllers\Admin\TicketController::updateStage`
- New behavior: rejects forward movement; cannot advance stages.

Stage back:

- HTTP: `PUT /admin/tickets/{ticket}/stage/back`
- Route: `admin.tickets.stage.back`
- Definition: `routes/web.php:113`
- Controller: `App\Http\Controllers\Admin\TicketController::moveBack`
- Service: `App\Services\Tickets\TicketLifecycleService::moveToStage`

Stage completion:

- HTTP: `PUT /admin/tickets/{ticket}/stages/{event}/complete`
- Route: `admin.tickets.stages.complete`
- Definition: `routes/web.php:114`
- Controller: `App\Http\Controllers\Admin\TicketController::completeStage`
- Service: `App\Services\Tickets\TicketLifecycleService::completeStage`
- Tables: `tickets.current_service_stage_id`, `tickets.status`, `ticket_stage_events.status`, `entered_at`, `completed_at`

Stage reopening:

- HTTP: `PUT /admin/tickets/{ticket}/stages/{event}/reopen`
- Route: `admin.tickets.stages.reopen`
- Definition: `routes/web.php:115`
- Controller: `App\Http\Controllers\Admin\TicketController::reopenStage`
- Service: `App\Services\Tickets\TicketLifecycleService::reopenStage`

File upload:

- HTTP: `POST /admin/tickets/{ticket}/files`
- Route: `admin.tickets.files.store`
- Definition: `routes/web.php:116`
- Controller: `App\Http\Controllers\Admin\TicketController::storeFile`
- Tables: `ticket_files`, optional `ticket_deliverables`

File visibility:

- HTTP: `PUT /admin/tickets/{ticket}/files/{file}/visibility`
- Route: `admin.tickets.files.visibility.update`
- Definition: `routes/web.php:117`
- Controller: `App\Http\Controllers\Admin\TicketController::updateFileVisibility`
- Tables: `ticket_files.is_client_visible`, `ticket_files.visibility`

Admin file download:

- HTTP: `GET /admin/tickets/{ticket}/files/{file}/download`
- Route: `admin.tickets.files.download`
- Definition: `routes/web.php:119`
- Controller: `App\Http\Controllers\TicketFileDownloadController::admin`
- Service: `App\Services\Tickets\TicketFileAccessService::assertAdminCanAccess`

Client ticket and file download:

- HTTP: `GET /portal/tickets/{ticket}`, `GET /portal/tickets/{ticket}/files/{file}/download`
- Routes: `client.tickets.show`, `client.tickets.files.download`
- Definition: `routes/web.php:141`, `routes/web.php:142`
- Controllers: `App\Http\Controllers\Client\TicketController::show`, `TicketFileDownloadController::client`
- Service: `App\Services\Tickets\TicketFileAccessService::assertClientCanAccess`

Public tracking and signed file download:

- HTTP: `GET /tracking`, `POST /tracking`, `GET /tracking/tickets/{ticket}/files/{file}`
- Routes: `tracking.index`, `tracking.show`, `tracking.files.download`
- Definition: `routes/web.php:57`, `routes/web.php:58`, `routes/web.php:61`
- Controllers: `App\Http\Controllers\Public\TicketTrackingController`, `TicketFileDownloadController::tracking`
- Middleware: `web`, plus `throttle:20,1` for lookup and `signed` for download
- Service: `App\Services\Tickets\TicketFileAccessService::assertSignedTrackingCanAccess`

# Email-language implementation

- Added `App\Support\Locales\RecipientLocaleResolver`.
- Client mail resolution order: valid `tickets.preferred_language`, valid assigned client `users.preferred_language`, app fallback.
- Admin mail resolution order: valid admin `users.preferred_language`, administrative default from `config('app.locale')`, app fallback.
- `ProjectNotificationService` now accepts translation keys and replacements, resolves locale before translating subject/body inputs, calls `PendingMail::locale()`, and also sets `Mailable::locale()` for serializable/queued correctness.
- Stage/service model replacements in mail copy are localized after recipient locale resolution.
- Admin browser/session locale no longer pre-translates client email content.

# Stage-transition implementation

- `TicketLifecycleService::completeStage` now locks ticket/event rows in the transaction, accepts only the current active event, rejects future/pending events, returns idempotently for duplicate completed events, marks the current event complete, and advances exactly one configured active stage.
- Final-stage completion sets `tickets.status` to `completed`.
- `TicketLifecycleService::moveToStage` now rejects forward movement and remains available for backward correction.
- `TicketController::updateStage` rejects attempts to advance through the old stage-selection route.
- Reopening is centralized in `TicketLifecycleService::reopenStage`; it reuses the selected event, accepts completed events, treats repeated reopen of the active current event as idempotent, clears `completed_at`, keeps the original `entered_at` when present, moves any other current event back to pending, and sets the ticket back to `in_progress`.
- The current schema has no immutable stage-history table, so reopening does not create a misleading text-only history record for the previous `completed_at` value. The current stage state remains queryable; prior completion timestamps are not durably queryable after reopen without a future history table.
- Demo seeding now advances demo tickets by explicitly completing current stages with notification suppression, rather than calling forward stage movement or sending operational mail.

# File-access implementation

- Added `App\Services\Tickets\TicketFileAccessService`.
- Added `TicketFile::scopeClientVisible`.
- Client and public tracking page queries use the shared visible scope.
- Downloads now use the shared access service for admin, authenticated client, and signed tracking contexts.
- Client and public tracking views now show visible general files even when a ticket also has deliverable groups.
- Source of truth is `ticket_files.is_client_visible` plus applicable linked deliverable checks; `visibility` is kept synchronized on writes but not used to hide legacy/demo records that already depend on the boolean.
- Client-visible linked files are denied when their deliverable belongs to another ticket or when the linked service deliverable is configured hidden by default. General visible files with no deliverable link remain visible.

# Exact change maps

## Change 1 - Recipient-specific ticket email locale

User-facing section: Public request emails, client project update emails, admin new-ticket alerts
HTTP method and URI: `POST /request`; admin stage/file routes that trigger emails
Laravel route name: `requests.store`, `admin.tickets.stages.complete`, `admin.tickets.stages.reopen`, `admin.tickets.files.store`, `admin.tickets.files.visibility.update`
Route-definition location: `routes/web.php:53`, `routes/web.php:114`, `routes/web.php:115`, `routes/web.php:116`, `routes/web.php:117`
Controller: `App\Http\Controllers\Public\ServiceRequestController::store`; `App\Http\Controllers\Admin\TicketController`
Service/domain method: `App\Services\Notifications\ProjectNotificationService::notifyTicket`, `notifyAdminsNewTicket`; `App\Support\Locales\RecipientLocaleResolver`
Mail/notification: `App\Mail\ProjectUpdateMail`, `App\Mail\AdminNewTicketMail`
Model/policy: `App\Models\Ticket`, `App\Models\User`
View/frontend: `resources/views/emails/project-update.blade.php`, `resources/views/emails/admin-new-ticket.blade.php`
Database: no schema change; reads `tickets.preferred_language`, `users.preferred_language`
Translation paths: `lang/es/site.php`, `lang/en/site.php`
Test path: `tests/Feature/TicketWorkflowIntegrityTest.php`
Previous behavior: callers translated strings under the active browser/session locale before sending.
New behavior: mail content is translated after resolving the recipient locale and assigned to the mailable locale.
Evidence: Focused mail tests and local mail-log entries for Spanish and English request confirmations.

## Change 2 - Explicit current-stage completion

User-facing section: Admin -> Tickets -> Stage completion
HTTP method and URI: `PUT /admin/tickets/{ticket}/stages/{event}/complete`; `PUT /admin/tickets/{ticket}/stage`
Laravel route name: `admin.tickets.stages.complete`; `admin.tickets.stage.update`
Route-definition location: `routes/web.php:114`, `routes/web.php:112`
Controller: `App\Http\Controllers\Admin\TicketController::completeStage`, `updateStage`
Service/domain method: `App\Services\Tickets\TicketLifecycleService::completeStage`, `moveToStage`, `reopenStage`
Mail/notification: `App\Mail\ProjectUpdateMail`
Model/policy: `App\Models\Ticket`, `App\Models\TicketStageEvent`, `App\Models\ServiceStage`
View/frontend: `resources/views/admin/tickets/show.blade.php`
Database: no schema change; updates `tickets.current_service_stage_id`, `tickets.status`, `ticket_stage_events.status`, `entered_at`, `completed_at`, `notes`
Translation paths: `lang/es/site.php`, `lang/en/site.php`
Test path: `tests/Feature/TicketWorkflowIntegrityTest.php`, `tests/Feature/AdminOperationsTest.php`
Previous behavior: the stage-selection route and service method could move a ticket forward without completing the current event.
New behavior: only explicit completion of the current event advances one configured stage; duplicate completion is idempotent; repeated reopen of the same current event is idempotent and leaves exactly one current event.
Evidence: Focused tests for rejected selection, direct service rejection, one-stage advancement, duplicate completion, future rejection, final completion, complete -> reopen -> complete coverage, repeated reopen, and reopened-ticket status.

## Change 3 - Centralized client file visibility and signed access

User-facing section: Client portal files, public tracking files, admin ticket files
HTTP method and URI: `GET /portal/tickets/{ticket}`, `GET /portal/tickets/{ticket}/files/{file}/download`, `GET /tracking/tickets/{ticket}/files/{file}`, `PUT /admin/tickets/{ticket}/files/{file}/visibility`
Laravel route name: `client.tickets.show`, `client.tickets.files.download`, `tracking.files.download`, `admin.tickets.files.visibility.update`
Route-definition location: `routes/web.php:141`, `routes/web.php:142`, `routes/web.php:61`, `routes/web.php:117`
Controller: `App\Http\Controllers\Client\TicketController::show`, `App\Http\Controllers\Public\TicketTrackingController::resolveTicket`, `App\Http\Controllers\TicketFileDownloadController`
Service/domain method: `App\Services\Tickets\TicketFileAccessService`
Mail/notification: `App\Mail\ProjectUpdateMail` for file availability
Model/policy: `App\Models\TicketFile::scopeClientVisible`
View/frontend: `resources/views/client/tickets/show.blade.php`, `resources/views/public/tracking.blade.php`, `resources/views/partials/ticket-file-card.blade.php`
Database: no schema change; reads and updates `ticket_files.is_client_visible`, keeps `ticket_files.visibility` synchronized
Translation paths: not changed
Test path: `tests/Feature/TicketWorkflowIntegrityTest.php`, `tests/Feature/AdminOperationsTest.php`, `tests/Feature/FunctionalBoundaryTest.php`
Previous behavior: visibility/download rules were duplicated inline and general visible files could be omitted when deliverables existed.
New behavior: download authorization is centralized; visible general files render immediately in client/tracking views; files linked to hidden service deliverables or deliverables owned by a different ticket are hidden from client/tracking lists and downloads.
Evidence: Focused tests for toggle-on/off rendering, visible linked deliverables, hidden linked deliverables, cross-ticket deliverable links, wrong client denial, unsigned/mismatched signed denial, valid signed access, admin access, and missing storage failure.

# Focused tests

- `php artisan test tests/Feature/TicketWorkflowIntegrityTest.php`
- Result after hardening review: 12 passed, 101 assertions.

# Complete regression tests

- `php artisan test`
- Final hardening result: 65 passed, 432 assertions.
- `npm run build`
- Final hardening result: passed; Vite produced the production bundle. Node emitted a `DEP0205` deprecation warning only.

# Local browser QA

- `GET http://127.0.0.1:8000` returned HTTP 200.
- `GET http://127.0.0.1:8000/login` returned HTTP 200.
- Runtime config verified by Artisan: `local|log|sync`.
- Route list verified 81 routes and expected middleware for public, admin, client, and signed tracking routes.

# Database impact

- No migration created.
- No schema change.
- Local QA created two local-only tickets for mail-log verification: `IGNA-2026-00005` and `IGNA-2026-00006`.
- Tests used `RefreshDatabase`.

# Security findings

- No production access occurred.
- No `.env` or backup file contents were inspected.
- File downloads now centralize ticket ownership, client ownership, signed URL, email-hash, visibility, linked-deliverable ownership, and storage existence checks.
- Unauthorized file responses remain 403 for invalid/unsigned signed route middleware and 404 for mismatched ownership/context.

# Files changed

- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Controllers/Client/TicketController.php`
- `app/Http/Controllers/Public/TicketTrackingController.php`
- `app/Http/Controllers/TicketFileDownloadController.php`
- `app/Models/TicketFile.php`
- `app/Services/Notifications/ProjectNotificationService.php`
- `app/Services/Tickets/TicketFileAccessService.php`
- `app/Services/Tickets/TicketLifecycleService.php`
- `app/Support/Locales/RecipientLocaleResolver.php`
- `database/seeders/DemoDataSeeder.php`
- `resources/views/admin/tickets/show.blade.php`
- `resources/views/client/tickets/show.blade.php`
- `resources/views/public/tracking.blade.php`
- `tests/Feature/AdminOperationsTest.php`
- `tests/Feature/TicketWorkflowIntegrityTest.php`
- `docs/codex/prompts/01-ticket-workflow-integrity.md`
- `docs/codex/sessions/01-ticket-workflow-integrity-result.md`

# Known baseline artifacts preserved

Accepted baseline paths were not modified intentionally. Graphify output was not regenerated.

# Remaining risks

- Reopen clears `ticket_stage_events.completed_at` for the reopened event and does not preserve the previous completion timestamp in notes as an authoritative record. A future immutable stage-history table is recommended if the business needs queryable completion/reopen cycles.
- Demo seeding uses explicit lifecycle completion with `notify: false`; operational mail is suppressed and tested.
- SQLite test runs verify the guarded service path and idempotence, but MySQL/InnoDB row-level lock behavior is stronger than SQLite's coarse locking model.

# Suggested commit groups

1. Email locale:
   `git add app/Support/Locales/RecipientLocaleResolver.php app/Services/Notifications/ProjectNotificationService.php app/Services/Tickets/TicketLifecycleService.php app/Http/Controllers/Admin/TicketController.php tests/Feature/TicketWorkflowIntegrityTest.php`
2. Stage integrity:
   `git add app/Services/Tickets/TicketLifecycleService.php app/Http/Controllers/Admin/TicketController.php resources/views/admin/tickets/show.blade.php database/seeders/DemoDataSeeder.php tests/Feature/AdminOperationsTest.php tests/Feature/TicketWorkflowIntegrityTest.php`
3. File visibility:
   `git add app/Services/Tickets/TicketFileAccessService.php app/Models/TicketFile.php app/Http/Controllers/TicketFileDownloadController.php app/Http/Controllers/Client/TicketController.php app/Http/Controllers/Public/TicketTrackingController.php resources/views/client/tickets/show.blade.php resources/views/public/tracking.blade.php tests/Feature/TicketWorkflowIntegrityTest.php`
4. Documentation:
   `git add docs/codex/prompts/01-ticket-workflow-integrity.md docs/codex/sessions/01-ticket-workflow-integrity-result.md`
