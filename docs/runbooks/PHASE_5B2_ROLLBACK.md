# Phase 5B.2 Rollback Runbook

## Rollback Target

- Branch: `release/phase-5b1-2026q3`
- Commit: `4e5888a46fbc745a0180ec15193e79e258c361c6`
- Laravel root: `/home/u935649387/apps/igna-studio`
- Public bridge: `/home/u935649387/domains/ignastudio.com/public_html/igna-app`

Application rollback does not recreate records permanently deleted after Phase 5B.2 deployment. Recover deleted records only from verified database and file backups.

## Commands

Run from the Hostinger Laravel root:

```bash
cd /home/u935649387/apps/igna-studio
php artisan down --render="errors::503" || php artisan down
git fetch origin release/phase-5b1-2026q3
git checkout release/phase-5b1-2026q3
git pull --ff-only origin release/phase-5b1-2026q3
test "$(git rev-parse HEAD)" = "4e5888a46fbc745a0180ec15193e79e258c361c6"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Restore the previous public bridge backup created during deployment:

```bash
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
BACKUP_DIR="$(ls -dt /home/u935649387/deployment-backups/phase-5b2-* | head -n 1)"
test "$PUBLIC_BRIDGE" = "/home/u935649387/domains/ignastudio.com/public_html/igna-app"
test -s "$BACKUP_DIR/public-bridge-igna-app.tar.gz"
tar -C /home/u935649387/domains/ignastudio.com/public_html -xzf "$BACKUP_DIR/public-bridge-igna-app.tar.gz"
```

Leave the additive `deletion_audits` table in place during ordinary code rollback. Do not roll back production migrations unless a separate database rollback has been reviewed and approved.

Exit maintenance mode and verify:

```bash
cd /home/u935649387/apps/igna-studio
php artisan up
curl -I https://ignastudio.com/
curl -I https://ignastudio.com/igna-app/favicon.ico
curl -I https://ignastudio.com/brand/favicon
tail -n 120 storage/logs/laravel.log
```

## Post-Rollback Checks

- Homepage, login, proposals, projects, tracking, and favicon routes return normal responses.
- No Laravel debug page appears.
- Logs contain no new rollback-related error.
- If records were deleted while Phase 5B.2 was active, decide separately whether to restore from database and file backups.
