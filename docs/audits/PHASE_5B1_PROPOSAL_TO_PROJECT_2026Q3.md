# Phase 5B.1 Proposal-to-Project Bridge - 2026 Q3

## Release Base

- Branch: `feature/proposal-to-project-bridge`
- Base commit: `e79d6781591f5617f92e6adde55df16b2abb59e7`
- Scope: Proposal-to-project conversion, project terminology, additive proposal/project metadata, focused regression tests, and documentation.
- Production access: none.
- Deployment: none.
- Commit/stage status: changes intentionally left unstaged for human review.

## Architecture

The implementation keeps the existing internal `Ticket` model, `tickets` table, route names, URL paths, ticket code generator, workflow events, deliverables, file authorization, tracking, and notification classes as the project engine. User-facing copy now presents that engine as Projects.

New bridge pieces:

- `tickets.proposal_id`: nullable, unique, foreign key to `proposals.id`, null on proposal deletion.
- `proposals.project_location`: nullable project location captured at proposal time.
- `proposals.requested_deadline`: nullable requested deadline captured at proposal time.
- `proposals.converted_to_project_at`: nullable conversion timestamp.
- `proposals.converted_by_user_id`: nullable administrator reference.
- `Proposal::project()`: one-to-one relationship to the generated project.
- `Ticket::proposal()`: source proposal relationship.
- `admin.proposals.projects.store`: POST-only admin conversion route.
- `ProposalProjectRequest`: validates active service category/service input and proposal eligibility.
- `CreateProjectFromProposal`: transactional conversion action.

## Eligibility

Only approved, unexpired proposals without an existing linked project can create a project. Draft, sent, rejected, expired, already-converted, and unauthorized attempts are rejected or redirected to the existing project.

One proposal can create at most one project. This is enforced by:

- Application recheck under row lock.
- Existing-project redirect for retries.
- Database unique constraint on `tickets.proposal_id`.

## Field Mapping

- Proposal title: maps to project name using the stable stored English title fallback.
- Proposal subject: maps to project description.
- Proposal client account or manual client name/email/phone: maps to project client fields.
- Proposal project location: maps to ticket `project_location`.
- Proposal requested deadline: maps to ticket `target_date`.
- Selected active service: maps to project `service_id`, public category, workflow stages, and deliverables.
- Existing ticket code generator: creates the project code.

Proposal totals, items, public token, PDF data, signatures, pricing notes, and template relationships are not copied or mutated by conversion.

There is no current company field in the source schema, so no company value is mapped.

## Workflow Initialization

The conversion reuses `TicketLifecycleService::initializeCatalogWorkflow()` so generated projects receive the same active service stages and deliverables as public intake projects. The first current stage receives a localized creation note:

- English: `Project created from approved proposal {proposal_number}.`
- Spanish: `Proyecto creado desde la propuesta aprobada {proposal_number}.`

The client project-created notification is scheduled after the database transaction commits. Mail failures are caught by the existing notification service and do not delete the project.

## Terminology Boundary

Visible operational module copy now uses:

- English: Project, Projects, Project code, Current stage, Open project, Create project, Source proposal.
- Spanish: Proyecto, Proyectos, Código del proyecto, Etapa actual, Abrir proyecto, Crear proyecto, Propuesta de origen.

Internal compatibility remains unchanged for:

- `Ticket` PHP classes.
- `tickets` table and `ticket_id` fields.
- Existing route names and URLs.
- Ticket code format.
- Existing mail class names.
- Public tracking compatibility.

Proposal/Propuesta terminology remains unchanged for proposal surfaces.

## Migration Review

Migration: `2026_08_04_000100_add_proposal_project_bridge.php`.

Characteristics:

- Additive nullable fields preserve existing data.
- Reversible `down()` verified on SQLite.
- MySQL-safe short constraint names are used in `up()`.
- SQLite rollback uses column-based foreign-key drops.
- Disposable SQLite rehearsal passed apply, rollback, and reapply.

Local app `php artisan migrate:status` shows the migration pending on the human's active local database because it was not applied there.

## Tests

Focused test added:

- `tests/Feature/ProposalToProjectBridgeTest.php`

Coverage includes:

- English/Spanish project terminology.
- Proposal terminology preservation.
- Eligibility for approved, draft, rejected, expired, and converted proposals.
- Authorization rejection for client users.
- Category/service validation.
- Inactive and mismatched service rejection.
- Missing client email rejection.
- Project creation and `proposal_id` relationship.
- Unique relationship and retry behavior.
- Field mapping for client, phone, project title, location, deadline, service, code, status, stages, deliverables.
- Stage creation note.
- Proposal item/total/status immutability.
- Project-created notification.
- Source proposal link on project detail.
- Public tracking compatibility.

Regression tests updated only where visible wording changed from ticket/request to project terminology.

## Verification

Passed:

- `php artisan test --filter=ProposalToProjectBridgeTest`: 6 tests, 82 assertions.
- Proposal administration focused tests.
- Proposal cost-template catalogue focused tests.
- Public service taxonomy focused tests.
- Service administration structured content focused tests.
- Ticket workflow focused tests.
- Ticket layout/document exchange focused tests.
- Team credential protection focused tests.
- `php artisan test`: 153 tests, 1353 assertions.
- `composer validate`.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm run build`.
- `git diff --check`.
- `php artisan route:list --except-vendor`.
- Disposable SQLite migration apply/rollback/reapply.
- Targeted Composer remediation: `guzzlehttp/guzzle` 7.15.1 -> 7.15.2 in `composer.lock`.
- `composer audit --locked`: no security vulnerability advisories found.
- `composer check-platform-reqs`: all current platform requirements satisfied on PHP 8.5.8.
- `composer install` and `composer install --dry-run`: lockfile reproducible.
- Isolated local QA SQLite database migrated and seeded with disposable local data.
- Local HTTP smoke: homepage, login, CSS, JavaScript, authenticated admin redirect, Spanish `Proyectos`, and English `Projects` verified.
- Local conversion smoke: `QA-5B1-AUTO` created one linked project; retry returned the same project; workflow stages and deliverables initialized.

Security remediation:

- `composer audit --locked` previously reported `guzzlehttp/guzzle` advisories published 2026-08-03:
  - `PKSA-gcrk-3vtt-1r14`, CVE-2026-69246, high, noncanonical host bypass, affected `<7.15.2`.
  - `PKSA-cnw1-2ytm-cgr8`, CVE-2026-69245, medium, noncanonical cookie domain issue, affected `<7.15.2`.
- Dry run proposed exactly one package update: `guzzlehttp/guzzle` 7.15.1 -> 7.15.2.
- Applied exactly that targeted update. `composer.json` was not changed.
- Current Composer audit is clean.

Development advisories:

- Full `npm audit` reports development-only advisories in `postcss`, `shell-quote` via `concurrently`, and `vite`.
- `npm audit --omit=dev` reports 0 production vulnerabilities.
- No npm dependency updates were performed in this phase.

## Local QA Environment

Created isolated QA assets under untracked `output/local-qa/phase-5b1/`.

Database:

- `output/local-qa/phase-5b1/igna-phase5b1-qa-20260805.sqlite`
- Environment overrides: local app, debug enabled locally, SQLite DB, file cache, file session, sync queue, log mailer.
- All migrations, including `2026_08_04_000100_add_proposal_project_bridge`, show `Ran`.

Local QA credentials are generated for each isolated QA environment and must not be reused in production.

Disposable QA records:

- Human approved proposal: `QA-5B1-APPROVED`.
- Human draft comparison proposal: `QA-5B1-DRAFT`.
- Automated conversion proposal: `QA-5B1-AUTO`.
- Eligible service: `qa-phase5b1-digital`, category `technology`.
- Mismatch service: `qa-phase5b1-engineering`, category `infrastructure_engineering`.

Startup helper:

- `output/local-qa/phase-5b1/start-local.sh`
- Runs Laravel on `http://127.0.0.1:8000` with the isolated SQLite database.
- Vite can be started separately with `npm run dev -- --host 127.0.0.1` when live asset refresh is required.

## Browser QA

Automated feature coverage verified the conversion, tracking, mail rendering, proposal PDF regressions, service localization, and credential fallback.

Local HTTP/authentication smoke was completed against the isolated QA database:

- Homepage: 200.
- Login page: 200.
- Built CSS asset: 200.
- Built JavaScript asset: 200.
- Superadministrator login: redirect to `/admin`.
- Spanish dashboard: `Proyectos`, no visible legacy `Tickets`/`Solicitudes` module label.
- English dashboard after locale switch: `Projects`, no visible legacy `Tickets`/`Solicitudes` module label.

Full point-and-click human browser QA remains required for the conversion modal, public tracking, PDF comparison, and console/network review.

## Graphify

Graphify-first reconnaissance was performed against the existing graph before source changes.

Post-implementation refresh was completed with:

```bash
uv tool run --from graphifyy graphify update .
```

Result:

- Re-extracted code files: 24/24 uncached files.
- Graph: 2211 nodes, 3780 edges, 260 communities.
- Warning: `skills-lock.json` produced zero nodes and will retry on a future run.
- Output location: local untracked `graphify-out/`.

Query validation confirmed the new bridge is discoverable through the graph, including `ProposalProjectController`, `ProposalProjectRequest`, `CreateProjectFromProposal`, `Proposal::project()`, `TicketLifecycleService::initializeCatalogWorkflow()`, `ProjectNotificationService`, and `ProposalToProjectBridgeTest`.

## Release Readiness

Phase 5B.1 source implementation and Composer security remediation are functionally complete. Human browser QA remains required before checkpoint/merge decisions.

Classification:

- Proposal-to-project bridge: READY.
- Project terminology: READY.
- Migration behavior: READY; isolated QA database migrated, active human database intentionally untouched.
- Full Laravel regression: READY.
- Frontend build: READY.
- Production npm audit: READY.
- Composer audit: READY.
- Full development npm audit: DEFERRED NON-BLOCKING FOR PRODUCTION, but requires follow-up.
- Manual authenticated browser QA: READY WITH MANUAL VALIDATION.

## Final Launch Readiness Addendum

Completed after the initial bridge checkpoint:

- Favicon delivery was repaired by replacing storage-symlink-dependent URLs with the public Laravel route `brand.favicon`.
- The Settings page now keeps the dedicated browser-icon uploader, shows the current favicon preview, hides raw branding storage paths, validates PNG/ICO uploads by MIME and decodability, and supports safe default restoration.
- Tracked fallback assets now exist for `/favicon.ico`, 16x16 PNG, 32x32 PNG, Apple touch icon, Android icons, and the web manifest.
- Project index and detail now show the existing `tickets.created_at` timestamp as the user-facing project creation date.
- Project index uses the proposal module's created-at sort contract: `sort=created_at&direction=asc|desc`, with safe fallback for invalid parameters.
- Dashboard copy now renders Recent Projects / Proyectos recientes and shows creation dates for recent projects.
- `igna:launch-reset` provides a guarded dry-run/force launch-data reset for future human execution after deployment and backups.

Updated verification:

- `php artisan test --filter=FaviconDeliveryTest`: 7 tests, 36 assertions.
- `php artisan test --filter=ProjectTimelineTest`: 5 tests, 27 assertions.
- `php artisan test --filter=LaunchDataResetTest`: 3 tests, 31 assertions.
- `php artisan test --filter=ProposalToProjectBridgeTest`: 6 tests, 82 assertions.
- `php artisan test`: 168 tests, 1447 assertions.
- `composer validate`, `composer audit --locked`, and `composer check-platform-reqs`: passed.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm audit`: development-only advisories remain in PostCSS, Vite, and shell-quote via concurrently.
- `npm run build`: passed with the existing Node `module.register()` deprecation warning.
- Disposable SQLite migration apply, rollback of `2026_08_04_000100_add_proposal_project_bridge`, and reapply passed.
- Graphify refresh: 2290 nodes, 3793 edges, 280 communities. The known `skills-lock.json` zero-node warning remains non-blocking.

Local browser/HTTP evidence:

- Homepage and login render the routed `/brand/favicon` URL with deterministic cache versioning.
- Routed favicon returns HTTP 200, `image/png`, cache headers, ETag, and `nosniff`.
- Static `/favicon.ico` returns HTTP 200 and `image/vnd.microsoft.icon`.
- In-app browser opened the resolved routed favicon directly as an image page.
- In-app browser verified Projects creation-date display, ascending sort URL, project detail creation date, and Spanish dashboard Recent Projects wording.
