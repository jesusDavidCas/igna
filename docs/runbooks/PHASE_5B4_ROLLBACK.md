# Phase 5B.4 Rollback

Scope: rollback of Blog publishing rendering, route-backed Blog header image delivery, and CommonMark lockfile update.

Phase 5B.3 rollback target:

```text
3b643313608fab959c25727f20b4e92baeee608f
```

## When To Roll Back

Roll back if production shows any of these release-blocking symptoms after Phase 5B.4 deployment:

- Public Blog article pages return 500 errors.
- `/blog/{slug}/header-image` fails for published Blog posts with valid stored header images.
- Blog header image uploads fail for valid JPG, PNG, or WebP files.
- Credential, ticket, proposal, or private storage files become publicly reachable through the Blog header-image route.
- Team photo delivery regresses.
- Proposal, sanitizer, or CommonMark-rendered content tests fail after deployment.

## Before Rollback

Do not delete Blog header images or storage directories. Capture:

- Current production commit.
- Laravel log excerpt around the failing request.
- `curl -I` result for a representative Blog article URL.
- `curl -I` result for the corresponding `/blog/{slug}/header-image` URL.
- Whether `storage/app/public/blog/headers` exists and is writable.

Do not print secrets, cookies, private paths, signed URLs, or credential contents.

## Rollback Procedure

Use the standard Hostinger Git rollback procedure to return the Laravel root to:

```text
3b643313608fab959c25727f20b4e92baeee608f
```

Then run the standard post-rollback Laravel cache rebuild and smoke checks from the deployment runbook.

## Database

No database rollback is required. Phase 5B.4 adds no migration.

## Public Assets

If the Phase 5B.4 public artifact was synchronized, restore the previous public `build/` assets from the deployment backup or redeploy the public assets for the rollback commit.

Do not synchronize or delete parent `public_html`. Operate only on the configured public bridge.

## Post-Rollback Checks

Confirm:

- Homepage loads.
- Public Blog index loads.
- Public Blog article loads.
- Admin login loads.
- Team profile photo route still behaves according to Phase 5B.3.
- Laravel logs contain no new rollback errors.

After rollback, reopen Phase 5B.4 locally before redeploying.
