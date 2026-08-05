# Phase 5B.2 Manual Launch Cleanup Runbook

## Purpose

This runbook explains how a human operator should approach launch cleanup now that guarded administrative deletion controls exist.

The normal recommendation is still:

1. deactivate records when historical context should remain;
2. preserve proposals, projects, users, services, and team members unless the record is clearly synthetic or erroneous;
3. use permanent deletion only after backup and review.

## What The Admin UI Can Delete

Superadministrators can permanently delete:

- isolated projects;
- unlinked proposals;
- non-protected users that are not the current account and do not violate the last-active-superadministrator invariant;
- team members and their credential files;
- services with no project/workflow history.

Administrators cannot permanently delete these records.

## Required Human Checklist

Before using a compact permanent-delete control:

1. Confirm the record is local/demo/test or otherwise approved for permanent deletion.
2. Confirm the relevant backup exists when reviewing production-like data.
3. Read the concise warning on the record page.
4. Open the confirmation modal.
5. Review the record identifier and consequence sentence.
6. Cancel unless the record is approved for permanent deletion.
7. If approved, choose Delete permanently.
8. Review the redirected index/detail page and flash message.
9. Check the deletion audit row exists without private paths or secret values.
10. Confirm linked proposals, preserved users, services, categories, templates, and unrelated records remain available after each cleanup group.

Typed confirmation is no longer part of the UI. The server still enforces superadministrator authorization, DELETE routes, CSRF, dependency checks, invariant protections, transactions, file cleanup, and deletion audit creation.

## Launch Reset Command

`php artisan igna:launch-reset` remains a CLI-only guarded launch reset tool. It is not a routine UI deletion control.

Safe preview:

```bash
php artisan igna:launch-reset
```

Mutation requires both flags and must be performed only after backups and explicit human approval:

```bash
php artisan igna:launch-reset --force --confirm=RESET-LAUNCH-DATA
```

Do not execute the launch reset command from routine admin cleanup, browser QA, scheduled tasks, deployment scripts, or production troubleshooting unless the release owner explicitly approves it for launch-data removal.

The Phase 5B.2 record-by-record controls are the intended production cleanup path for approved launch remnants. They are deliberately slower than the bulk reset command because they preserve review context and create audit rows per deleted record.

## Production Reminder

Do not delete real production client records without a verified backup, a written approval trail, and a rollback plan.
