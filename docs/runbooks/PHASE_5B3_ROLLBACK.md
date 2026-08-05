# Phase 5B.3 Rollback

Scope: rollback of Team photo delivery changes only.

Phase 5B.2 rollback target:

```text
c56832d83d36cb07d35f1090b428e95f4121af56
```

## When To Roll Back

Roll back if production shows any of these release-blocking symptoms after Phase 5B.3 deployment:

- Public Team pages return 500 errors.
- `/team/{slug}/photo` fails for active Team Members with valid stored photos.
- Team photo uploads fail for valid JPG, PNG, or WebP files.
- Credential, ticket, proposal, or private storage files become publicly reachable through the Team photo route.
- Admin Team photo replacement deletes a file still referenced by another Team Member.

## Before Rollback

Do not delete Team photos or storage directories. Capture:

- Current production commit.
- Laravel log excerpt around the failing request.
- `curl -I` result for a representative `/team/{slug}/photo` URL.
- Whether PHP GD is available.
- Whether `storage/app/public/team/photos` is writable.

Do not print secrets, cookies, private paths, signed URLs, or credential contents.

## Rollback Procedure

Use the standard Hostinger Git rollback procedure to return the Laravel root to:

```text
c56832d83d36cb07d35f1090b428e95f4121af56
```

Then run the standard post-rollback Laravel cache rebuild and smoke checks from the deployment runbook.

## Database

No database rollback is required. Phase 5B.3 adds no migration.

## Public Assets

If the Phase 5B.3 public artifact was synchronized, restore the previous public `build/` assets from the deployment backup or redeploy the public assets for the rollback commit.

Do not synchronize or delete parent `public_html`. Operate only on the configured public bridge.

## Post-Rollback Checks

Confirm:

- Homepage loads.
- Public Team profile loads.
- Admin login loads.
- Team photo behavior matches Phase 5B.2 behavior.
- Laravel logs contain no new rollback errors.

After rollback, reopen Phase 5B.3 locally before redeploying.
