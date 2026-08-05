# Phase 5B.2 Guarded Deletion Controls Result

## Summary

Implemented guarded, superadministrator-only permanent deletion controls for projects, proposals, users, team members, and services. Proposal-template deletion was preserved.

Phase 5B.2 changes remain unstaged and uncommitted for human review.

Final release review then hardened shared physical-file cleanup and last-superadministrator concurrency before committing the release candidate.

## Architecture

The implementation uses:

- explicit DELETE routes for each domain;
- `GuardedDeletionRequest` for superadministrator authorization;
- `DeletionImpact` for backend dependency checks and audit summaries;
- domain services for transactional deletion and server-side dependency rechecks;
- `DeletionAudit` plus the `deletion_audits` table for append-only deletion metadata.

## Routes

| Domain | Route name | Method | Path |
| --- | --- | --- | --- |
| Project | `admin.tickets.destroy` | DELETE | `/admin/tickets/{ticket}` |
| Proposal | `admin.proposals.destroy` | DELETE | `/admin/proposals/{proposal}` |
| User | `admin.users.destroy` | DELETE | `/admin/users/{user}` |
| Team member | `admin.team.destroy` | DELETE | `/admin/team/{teamMember}` |
| Service | `admin.services.destroy` | DELETE | `/admin/services/{service}` |

## Authorization

Administrators keep their existing view/update access where already allowed, but cannot permanently delete these records. The request layer requires `User::isSuperAdmin()`, and tests verify administrator DELETE requests are forbidden.

## Confirmation UX

The original large danger-zone UI was replaced after human QA with compact, superadministrator-only controls:

- a short title;
- a one-sentence irreversible-action warning;
- one Delete permanently button;
- a small confirmation modal with Cancel and Delete permanently.

Typed confirmation was removed. The normal page no longer displays dependency-count matrices, delete/preserve columns, or raw technical dependency summaries. The server still enforces authorization, CSRF, DELETE routes, route-model binding, transactions, dependency rechecks, and deletion audits.

The modal keeps Cancel as the initial safe focus, traps focus while open, closes with Escape or Cancel, returns focus to the trigger, and prevents duplicate destructive submissions.

## Active QA Database Repair

The browser was using `storage/app/qa/local-preview.sqlite`, not an output scratch database. That database had not received:

- `2026_08_04_000100_add_proposal_project_bridge`
- `2026_08_05_000100_create_deletion_audits_table`

Those migrations were applied to the exact QA database. Post-repair checks confirmed `tickets.proposal_id` and `deletion_audits` exist. No schema fallback was added to business logic.

## Project Behavior

Project deletion removes:

- ticket row;
- stage events;
- stage audits;
- project deliverables;
- ticket file records;
- private stored ticket files.

It preserves:

- source service;
- users and clients;
- team members;
- linked proposal/items/totals/status/public token.

For linked proposals, `converted_to_project_at` and `converted_by_user_id` are cleared.

Final hardening preserves a physical project file when another `ticket_files` row still references the same disk/path.

## Proposal Behavior

Linked proposals are blocked until the linked project is deleted. Unlinked proposals and proposal items are deleted while templates, services, users, and projects remain untouched.

## User Behavior

Deletion blocks the current authenticated user, the protected launch superadministrator when present, and the last active superadministrator invariant. Targeted user deletion revokes sessions/remember state, deletes database sessions and password reset tokens, deletes user-specific signature files, clears database notifications when the table exists, and preserves operational records through existing nullable FKs. The final review locks active superadministrator rows where supported before enforcing the last-active-superadministrator invariant.

## Team-Member Behavior

Team-member deletion removes the profile, credential records, credential view metadata, local original credential files, local protected derivatives, and an exclusive profile photo. It does not delete users or operational/project records. Local credential files are not removed when another credential record still references the same path.

## Service Behavior

Service deletion blocks any historical project/workflow dependency and recommends deactivation. Unused services delete only their owned stages and deliverables.

## Launch Reset

`igna:launch-reset` was reviewed only. It remains CLI-only, not routed, not scheduled, and documented as a guarded launch cleanup command requiring explicit confirmation.

## Tests

Focused test class added:

- `tests/Feature/GuardedAdministrativeDeletionTest.php`

Focused result:

```text
php artisan test --filter=GuardedAdministrativeDeletionTest
10 passed, 129 assertions
```

Targeted regression result:

```text
php artisan test --filter='GuardedAdministrativeDeletionTest|ProposalToProjectBridgeTest|ProposalCostTemplateCatalogueTest|TeamCredentialProtectionTest|ServiceAdministrationStructuredContentTest|PublicServiceTaxonomyRequestTest|TicketLayoutDocumentExchangeTest|AuthenticationRevocationTest|LaunchDataResetTest'
77 passed, 748 assertions
```

Full regression result:

```text
php artisan test
178 passed, 1576 assertions
```

## Build, Audit, And Status Gate

- `composer validate`: passed.
- `composer audit --locked`: no security vulnerability advisories found.
- `composer check-platform-reqs`: passed locally on PHP 8.5.8.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm run build`: passed with the existing Node `module.register()` deprecation warning.
- `npm audit`: development-only advisories remain in `postcss`, `shell-quote` via `concurrently`, and `vite`; no dependency updates were performed.
- `git diff --check`: passed.
- `php artisan route:list --except-vendor`: generated successfully and includes `admin.tickets.destroy`, `admin.proposals.destroy`, `admin.users.destroy`, `admin.team.destroy`, and `admin.services.destroy`.
- `php artisan migrate:status`: the active QA database now has the Phase 5B.1 bridge migration and Phase 5B.2 audit migration applied.

HTTP QA against the active QA server verified `/admin/proposals/5` returns HTTP 200, all five deletion pages render compact controls without the old matrix/input, and a disposable QA-only user deletion writes a deletion audit row.

## Migration Rehearsal

The `deletion_audits` migration was applied, rolled back, and reapplied against a disposable SQLite database under `output/security-review/phase-5b2/`. No human local records were destroyed.

## Graphify Refresh

Graphify was refreshed after source changes:

```text
uv tool run --from graphifyy graphify update .
2432 nodes, 4278 edges, 288 communities
```

Smoke queries found the new compact deletion components, deletion services, `DeletionImpact`, `DeletionAudit`, the authorization request, and the explicit deletion route/controller paths.

Graphify warned that `skills-lock.json` produced zero nodes. That is a baseline non-code artifact and was left uncommitted.

## Human Review Notes

Use isolated local records for manual browser QA. Do not delete meaningful client records while reviewing. Confirm that compact deletion controls appear only to superadministrators and that normal deactivate/edit flows remain visible where expected.
