# Phase 5B.1 Launch Data Reset Runbook

## Purpose

Reset operational launch data only after Phase 5B.1 is deployed, validated, backed up, and separately approved.

## Preserved Superadministrator

`jesus.castaneda@ignastudio.com`

## Dry Run

From the Laravel root, after deployment validation and backups:

```bash
php artisan igna:launch-reset
```

Review the reported counts for:

- projects
- project files
- project deliverables
- stage events
- stage audits
- proposals
- proposal items
- proposal/project links
- non-superadministrator users
- sessions

## Force Execution

Run only after explicit human approval:

```bash
php artisan igna:launch-reset --force --confirm=RESET-LAUNCH-DATA
```

## Preserved Data

- Preserved superadministrator.
- Services.
- Service stages.
- Service deliverables.
- Proposal templates.
- Proposal-template items.
- Settings.
- Branding and favicon configuration.
- Blog content.
- Team content.
- Migrations.
- Launch master data.

## Deleted Data

- Projects/tickets.
- Project/ticket files.
- Stage events and audits.
- Proposals.
- Proposal items.
- Proposal/project links.
- Non-superadministrator users.
- Related sessions where stored in the database.

## Required Post-Reset Checks

- Preserved superadministrator can sign in.
- Services remain.
- Categories/business lines remain.
- Workflow stages and deliverables remain.
- Proposal templates remain.
- Branding/favicon settings remain.
- Zero projects.
- Zero proposals.
- Only intended users remain.
- Laravel logs have no reset error.

Do not run reset automatically from deployment scripts.
