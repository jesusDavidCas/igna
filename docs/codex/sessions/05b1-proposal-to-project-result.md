# Phase 5B.1 Proposal-to-Project Bridge Result

## Objective

Implement the first Phase 5B bridge so approved proposals can initialize real operational projects while preserving the existing internal Ticket engine and changing visible module terminology to Project/Proyecto.

## Branch

- Working branch: `feature/proposal-to-project-bridge`
- Started from deployed release commit: `e79d6781591f5617f92e6adde55df16b2abb59e7`
- The local release closeout commit above the deployed commit was preserved on `release/phase-5a-2026q3`; this branch intentionally starts from the deployed baseline.

## Implementation Summary

Added an additive proposal/project relationship:

- `tickets.proposal_id`
- `proposals.project_location`
- `proposals.requested_deadline`
- `proposals.converted_to_project_at`
- `proposals.converted_by_user_id`

Added relationships:

- `Proposal::project()`
- `Proposal::convertedBy()`
- `Ticket::proposal()`

Added conversion path:

- Route: `POST /admin/proposals/{proposal}/project`
- Route name: `admin.proposals.projects.store`
- Controller: `App\Http\Controllers\Admin\ProposalProjectController`
- Request: `App\Http\Requests\Admin\ProposalProjectRequest`
- Action service: `App\Services\Proposals\CreateProjectFromProposal`

The conversion service locks and rechecks the proposal, creates one linked project, reuses the existing ticket code generator, initializes stages and deliverables through `TicketLifecycleService`, records a localized first-stage note, updates conversion metadata, and sends the client project-created mail after commit.

## Route and Authorization Map

- Proposal detail: `GET admin/proposals/{proposal}`, admin/superadministrator only.
- Create project from proposal: `POST admin/proposals/{proposal}/project`, admin/superadministrator only.
- Project detail: `GET admin/tickets/{ticket}`, unchanged route name/path, admin/superadministrator only.
- Public tracking: unchanged `GET/POST tracking`.
- Client portal: unchanged `portal/tickets/{ticket}` routes.

Direct client access to the conversion route is rejected by the existing role middleware and covered by focused tests.

## Data Mapping

Mapped:

- Proposal title to project name.
- Proposal subject to project description.
- Proposal client/manual contact fields to project contact fields.
- Proposal project location to project location.
- Proposal requested deadline to target date.
- Selected service/category to project service and workflow.

Not mapped:

- Proposal totals.
- Proposal items.
- Proposal PDF data.
- Proposal signatures.
- Proposal public token.
- Proposal template relationships.
- Company, because no company field exists in the current schema.

## Terminology

Visible operational copy now presents the engine as projects:

- `Tickets` -> `Projects`
- `Ticket code` -> `Project code`
- `Open ticket` -> `Open project`
- `Create project from proposal`
- `Source proposal`

Spanish project copy:

- `Proyectos`
- `Proyecto`
- `Código del proyecto`
- `Crear proyecto desde la propuesta`
- `Abrir proyecto`
- `Propuesta de origen`

Technical class names, database tables, route names, and URLs intentionally remain compatible.

## Tests Added and Updated

Added:

- `tests/Feature/ProposalToProjectBridgeTest.php`

Updated old terminology assertions in:

- `tests/Feature/TicketWorkflowIntegrityTest.php`
- `tests/Feature/AdminOperationsTest.php`

Focused coverage:

- Eligibility.
- Validation.
- Authorization.
- Idempotency and unique database constraint.
- Field mapping.
- Workflow initialization.
- Proposal immutability.
- Mail notification.
- Source proposal link.
- Public tracking compatibility.
- English and Spanish terminology.

## Verification Results

Passed:

- `php artisan test --filter=ProposalToProjectBridgeTest`: 6 tests, 82 assertions.
- Focused proposal, service, ticket, and credential regression bands.
- `php artisan test`: 153 tests, 1353 assertions.
- `composer validate`.
- `npm audit --omit=dev`.
- `npm run build`.
- `git diff --check`.
- `php artisan route:list --except-vendor`.
- `php artisan migrate:status`.
- Disposable SQLite migration apply/rollback/reapply.
- Targeted Composer dry run proposed only `guzzlehttp/guzzle` 7.15.1 -> 7.15.2.
- Targeted Composer update changed only `composer.lock`.
- `composer audit --locked`: no security vulnerability advisories found.
- `composer check-platform-reqs`: passed on PHP 8.5.8.
- `composer install` and `composer install --dry-run`: reproducible from lockfile.
- Isolated SQLite QA database migrated and seeded.
- Local authentication smoke passed for the disposable local superadministrator.
- Local HTTP smoke passed for homepage, login, built CSS, built JavaScript, admin redirect, Spanish `Proyectos`, and English `Projects`.
- Automated conversion smoke created one project from `QA-5B1-AUTO`; retry returned the same project.

Resolved security blocker:

- `composer audit --locked` previously reported `PKSA-gcrk-3vtt-1r14` / CVE-2026-69246 and `PKSA-cnw1-2ytm-cgr8` / CVE-2026-69245 for `guzzlehttp/guzzle` 7.15.1.
- `guzzlehttp/guzzle` is now locked at 7.15.2.
- Current Composer audit is clean.

Deferred:

- Full `npm audit` development-only advisories remain in `postcss`, `shell-quote` through `concurrently`, and `vite`.
- `npm audit --omit=dev` remains clean.

## Local QA Environment

Created under untracked `output/local-qa/phase-5b1/`:

- `igna-phase5b1-qa-20260805.sqlite`.
- `start-local.sh`.
- `bootstrap-qa-data.php`.
- `conversion-smoke.php`.

Local QA credentials are generated for each isolated QA environment and must not be reused in production.

Human browser QA records:

- Approved proposal: `QA-5B1-APPROVED`.
- Draft comparison proposal: `QA-5B1-DRAFT`.
- Eligible service: `qa-phase5b1-digital`, category `technology`.
- Mismatch service: `qa-phase5b1-engineering`, category `infrastructure_engineering`.

## Browser Validation

Manual point-and-click browser QA remains required. Automated local HTTP/auth smoke completed successfully against the isolated QA database and verified dashboard terminology in Spanish and English.

## Graphify

Graphify-first reconnaissance was completed before implementation.

Post-change Graphify refresh was completed with:

- `uv tool run --from graphifyy graphify update .`
- Result: 2211 nodes, 3780 edges, 260 communities.
- Warning: `skills-lock.json` produced zero nodes and will retry on a future run.

Query validation found the new conversion route/controller/request/action service, proposal and ticket relationships, lifecycle workflow reuse, notification path, and focused test coverage in the refreshed graph.

## Final State

The final launch-readiness pass added the favicon repair, project creation-date timeline UX, guarded launch reset command, focused tests, browser evidence, Graphify refresh, and release packaging documentation on top of the proposal-to-project bridge.

Current verification:

- `php artisan test`: 168 tests, 1447 assertions.
- Focused bridge, favicon, timeline, and launch-reset tests passed.
- Composer validation, audit, and platform checks passed.
- `npm audit --omit=dev` passed with zero production vulnerabilities.
- Full `npm audit` still reports development-only advisories in PostCSS, Vite, and shell-quote via concurrently.
- Frontend production build passed.
- Disposable SQLite migration apply, rollback, and reapply passed.
- In-app browser verified routed favicon loading and Projects creation-date/sort/detail/dashboard behavior.

The release candidate must still be pushed and deployed only after explicit human approval.
