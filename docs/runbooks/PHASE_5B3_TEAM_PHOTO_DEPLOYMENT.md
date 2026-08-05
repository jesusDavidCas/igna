# Phase 5B.3 Team Photo Deployment Notes

Scope: Team photo delivery repair only.

No production commands were executed during implementation.

## Pre-Deployment Checks

Run from the Laravel root:

```bash
git branch --show-current
git rev-parse HEAD
php artisan route:list --except-vendor | grep 'team.photo'
php -r "echo extension_loaded('gd') ? 'GD=PASS'.PHP_EOL : 'GD=MISSING'.PHP_EOL;"
test -d storage/app/public/team/photos || mkdir -p storage/app/public/team/photos
test -w storage/app/public/team/photos && echo "TEAM_PHOTO_STORAGE_WRITABLE=PASS"
```

## Deployment Behavior

Existing Team photo records remain valid when `photo_path` is under:

```text
team/photos/
```

After deployment, public Team photo requests should use:

```text
/team/{team-member-slug}/photo
```

They should not use:

```text
/storage/team/photos/...
```

## Smoke Test

Use a non-sensitive active Team Member with a stored photo:

```bash
php artisan route:list --except-vendor | grep 'team.photo'
curl -I https://ignastudio.com/igna-app/team/{team-member-slug}/photo
```

Expected:

```text
HTTP/2 200
content-type: image/jpeg
x-content-type-options: nosniff
cache-control: public, max-age=604800
```

If the stored file is missing or invalid, the endpoint should still return:

```text
HTTP/2 200
content-type: image/png
```

The PNG is the generated initials fallback. Do not treat that fallback as proof that the original file exists.

## Rollback

Rollback to the previous release commit using the standard Phase 5B runbook. No database rollback is required because Phase 5B.3 adds no migration.

If only Team photo rendering must be inspected during rollback, compare:

```text
/team/{team-member-slug}/photo
/storage/team/photos/{stored-file}
```

The corrected release should not depend on the second path.
