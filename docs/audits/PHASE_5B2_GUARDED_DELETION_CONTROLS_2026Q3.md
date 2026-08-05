# Phase 5B.2 Guarded Deletion Controls - 2026 Q3

## Scope

Phase 5B.2 adds narrowly guarded permanent deletion controls for administrative cleanup of five operational domains:

- Projects, implemented by the internal `Ticket` model.
- Proposals.
- Users.
- Team members.
- Services.

Proposal-template deletion already existed and was preserved. No deployment, production access, dependency update, staging, commit, merge, or push was performed in this phase.

## Starting State

- Starting branch: `release/phase-5b1-2026q3`.
- Starting commit: `4e5888a46fbc745a0180ec15193e79e258c361c6`.
- Working branch created: `feature/guarded-admin-deletion-controls`.
- Baseline untracked local artifacts were preserved.

## Graphify Reconnaissance

Graphify was queried first for administrative destroy/delete routes, the `Ticket`, `Proposal`, `User`, `TeamMember`, `Service`, credential, file, and superadministrator paths. The scoped graph pointed to:

- `routes/web.php`
- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Controllers/Admin/ProposalController.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/TeamMemberController.php`
- `app/Http/Controllers/Admin/ServiceController.php`
- `app/Http/Controllers/Admin/ProposalServiceTemplateController.php`
- `app/Models/Ticket.php`
- `app/Models/Proposal.php`
- `app/Models/User.php`
- `app/Models/TeamMember.php`
- `app/Models/TeamCredential.php`
- `app/Models/Service.php`
- existing Phase 5A/5B test classes covering proposals, ticket files, credentials, services, and launch reset behavior.

Source verification then inspected the controllers, routes, models, migrations, and existing proposal-template delete path directly.

## Shared Safety Architecture

Deletion is intentionally explicit per domain:

- `DELETE /admin/tickets/{ticket}`
- `DELETE /admin/proposals/{proposal}`
- `DELETE /admin/users/{user}`
- `DELETE /admin/team/{teamMember}`
- `DELETE /admin/services/{service}`

The shared request layer is `App\Http\Requests\Admin\GuardedDeletionRequest`.

The request layer:

- authorizes only `User::isSuperAdmin()`;
- intentionally no longer requires typed confirmation;
- keeps all authorization, route-model binding, CSRF, and DELETE-method requirements;
- leaves dependency and invariant enforcement in the transactional deletion services.

The initial Phase 5B.2 UI used a large danger-zone card with dependency matrices, delete/preserve columns, and typed confirmation. Human QA found that presentation too dominant for normal record pages. The UX refinement replaced it with compact secondary controls and concise confirmation modals while preserving server-side safeguards.

The shared impact object is `App\Support\Deletion\DeletionImpact`.

The deletion services are:

- `App\Services\Deletion\DeleteProject`
- `App\Services\Deletion\DeleteProposal`
- `App\Services\Deletion\DeleteUser`
- `App\Services\Deletion\DeleteTeamMember`
- `App\Services\Deletion\DeleteService`

Each service rechecks the database inside a transaction and uses row locks where supported by the configured database.

## Deletion Audit

An append-only `deletion_audits` table was added. Audit rows record:

- actor user id when available;
- actor email snapshot;
- entity type;
- entity public identifier;
- entity label;
- dependency summary counts;
- creation timestamp.

Audit rows do not store credentials, private storage paths, cookies, tokens, signed URLs, email bodies, file contents, or full deleted records.

## Compact Deletion UX

Permanent deletion controls now use:

- `resources/views/components/admin/compact-delete-control.blade.php`
- `resources/views/components/admin/delete-confirmation-modal.blade.php`

The normal page shows only a compact title, one-sentence warning, and a single destructive button. The modal contains a concise confirmation question, the record identifier, Cancel, and Delete permanently. There is no typed confirmation input, dependency-count matrix, delete/preserve column layout, or raw dependency JSON in the normal UI.

The modal traps keyboard focus, starts on Cancel, closes with Escape or Cancel, returns focus to the trigger, prevents background scroll, and disables duplicate submission after the destructive form is submitted.

Service deletion is placed in the service edit action footer opposite Save changes. Other deletion controls are placed at the bottom of their record pages.

## Active QA Database Repair

The active local QA database was confirmed as:

```text
storage/app/qa/local-preview.sqlite
```

Before repair, this database was missing the Phase 5B.1 `tickets.proposal_id` column and the Phase 5B.2 `deletion_audits` table. The exact database was migrated without destroying or reseeding QA records:

```text
2026_08_04_000100_add_proposal_project_bridge: ran
2026_08_05_000100_create_deletion_audits_table: ran
```

Post-repair verification confirmed `tickets.proposal_id` and `deletion_audits` exist. No application schema-existence fallback was added.

## Domain Decisions

### Project

Project deletion deletes project stage events, stage audits, deliverables, ticket file rows, and private stored project files. It preserves services, users, team members, proposal templates, and linked proposals.

When a project came from a proposal, the proposal is preserved and only project-conversion metadata on that proposal is cleared.

Final hardening added a shared-file guard so post-transaction storage cleanup skips a stored project file when another `ticket_files` record still references the same disk/path.

### Proposal

Proposal deletion is blocked when a linked project exists. The localized message is:

- English: `This proposal is linked to project {project_code}. Delete the project first.`
- Spanish: `Esta propuesta está vinculada al proyecto {project_code}. Elimina primero el proyecto.`

Unlinked proposal deletion removes the proposal and proposal item rows. It preserves services, users, projects, and reusable proposal templates.

### User

User deletion is blocked for:

- the current authenticated account;
- the protected launch superadministrator account when present as `jesus.castaneda@ignastudio.com` with the superadministrator role;
- the last active superadministrator invariant.

Deletion revokes authentication state, removes database sessions and password reset tokens when those tables exist, clears database notifications when present, reassigns mutable blog author/editor ownership to the acting user, deletes the saved signature asset, and relies on existing nullable foreign keys to preserve project/proposal history. The last-active-superadministrator count is checked while locking active superadministrator rows where the production database supports row locks.

User deletion does not delete team members.

### Team Member

Team-member deletion deletes the team profile, owned credential records, credential view rows, original private credential files, protected derivative files, and exclusive profile photo. It preserves users, services, proposals, projects, and templates.

Final hardening added a shared-file guard so credential cleanup skips local original/protected paths still referenced by another credential record.

### Service

Service deletion is blocked by project or workflow history:

- projects using the service;
- projects using owned current stages;
- ticket stage events;
- ticket stage audits;
- ticket deliverables referencing owned service deliverables.

Unused services delete their owned stages and deliverables through existing cascade constraints. Categories, other services, proposal templates, projects, proposals, users, and settings are preserved.

## Launch Reset Review

`igna:launch-reset` remains a guarded CLI-only launch cleanup command. It is registered in `bootstrap/app.php`, is not exposed through routes or UI navigation, and has no scheduler registration. It requires `--force --confirm=RESET-LAUNCH-DATA` for mutation. It must not be used as a routine deletion control.

## Automated Verification

Focused deletion verification:

```text
php artisan test --filter=GuardedAdministrativeDeletionTest
10 passed, 129 assertions
```

Targeted Phase 5A/5B regression band:

```text
php artisan test --filter='GuardedAdministrativeDeletionTest|ProposalToProjectBridgeTest|ProposalCostTemplateCatalogueTest|TeamCredentialProtectionTest|ServiceAdministrationStructuredContentTest|PublicServiceTaxonomyRequestTest|TicketLayoutDocumentExchangeTest|AuthenticationRevocationTest|LaunchDataResetTest'
77 passed, 748 assertions
```

Full Laravel regression:

```text
php artisan test
178 passed, 1576 assertions
```

Build and audit gate:

- `composer validate`: passed.
- `composer audit --locked`: no security vulnerability advisories found.
- `composer check-platform-reqs`: passed for the local PHP 8.5.8 environment.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm run build`: passed; existing Node `module.register()` deprecation warning remains.
- `npm audit`: development-only advisories remain in `postcss`, `shell-quote` through `concurrently`, and `vite`. No dependency updates were performed in this phase.
- `git diff --check`: passed.
- `php artisan route:list --except-vendor`: route list generated and includes the five explicit deletion routes.
- `php artisan migrate:status`: the active QA database `storage/app/qa/local-preview.sqlite` now reports the Phase 5B.1 bridge migration and Phase 5B.2 audit migration as ran.

Local HTTP QA against the active QA database verified:

- `GET /admin/proposals/5`: HTTP 200.
- `GET /admin/tickets/7`: HTTP 200.
- `GET /admin/users/10/edit`: HTTP 200.
- `GET /admin/team/4/edit`: HTTP 200.
- `GET /admin/services/2/edit`: HTTP 200.
- Each page rendered compact deletion controls or the service action-footer delete button.
- No typed confirmation input or large dependency matrix rendered.
- Deleting one disposable QA-only user completed with redirect and wrote a deletion audit row.

Migration rehearsal was performed against a disposable SQLite database under `output/security-review/phase-5b2/`. The new `deletion_audits` migration applied, rolled back, and reapplied successfully.

Graphify was refreshed with `uv tool run --from graphifyy graphify update .`.

Graphify result:

```text
2432 nodes, 4278 edges, 288 communities
```

Graphify warning:

```text
skills-lock.json produced zero nodes
```

This warning is tied to a baseline non-code artifact and does not affect the deletion source graph.

## Remaining Risks

- Permanent deletion remains intentionally dangerous and should be used only after backups and human review.
- Storage deletion failures are reported and do not expose private paths to the user.
- Browser QA should still be completed by the human across desktop, tablet, and mobile widths using isolated local records before any release approval.
- Development-only npm advisories remain documented for a separate dependency-maintenance pass.
