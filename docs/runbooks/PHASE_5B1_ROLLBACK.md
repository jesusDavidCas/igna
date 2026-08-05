# Phase 5B.1 Rollback Runbook

## Purpose

Rollback Phase 5B.1 deployment if production validation fails.

## Before Migrations

If failure occurs before migrations:

1. Keep or enter maintenance mode.
2. Return production source to the previous commit `e79d6781591f5617f92e6adde55df16b2abb59e7`.
3. Run `composer install` from the previous lockfile.
4. Restore previous public bridge assets from backup.
5. Clear and rebuild caches.
6. Leave maintenance mode after smoke tests pass.

## After Migrations

If failure occurs after the additive Phase 5B.1 migration:

1. Keep or enter maintenance mode.
2. Prefer source rollback without database rollback if the failure is application asset or code related; the new nullable columns are backward-compatible.
3. If database rollback is required, restore the verified database backup rather than running destructive commands on production.
4. Restore public bridge assets from backup.
5. Run `composer install` from the restored lockfile.
6. Clear and rebuild caches.
7. Smoke test homepage, login, project list, proposal list, tracking, and favicon.
8. Leave maintenance mode only after validation passes.

## Launch Reset Rollback

Launch-data reset is separate from deployment. If reset has been forced and must be undone, restore the verified database and file backups from immediately before reset. Do not attempt to reconstruct deleted operational data manually.
