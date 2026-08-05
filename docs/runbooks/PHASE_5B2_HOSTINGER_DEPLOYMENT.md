# Phase 5B.2 Hostinger Deployment Runbook

## Scope

Deploy `release/phase-5b2-2026q3` from Phase 5B.1 production baseline `4e5888a46fbc745a0180ec15193e79e258c361c6`.

Do not run `php artisan igna:launch-reset`.

## Fixed Paths

- Laravel root: `/home/u935649387/apps/igna-studio`
- Public bridge: `/home/u935649387/domains/ignastudio.com/public_html/igna-app`
- Releases: `/home/u935649387/releases`
- Backups: `/home/u935649387/deployment-backups`

Never synchronize or delete `/home/u935649387/domains/ignastudio.com/public_html`. Operate only on the `igna-app` bridge.

## Commands

Run from the Hostinger Laravel root unless noted:

```bash
cd /home/u935649387/apps/igna-studio
pwd
git branch --show-current
git rev-parse HEAD
git status --short
test "$(git rev-parse HEAD)" = "4e5888a46fbc745a0180ec15193e79e258c361c6"
test -d /home/u935649387/domains/ignastudio.com/public_html/igna-app
test ! -e /home/u935649387/domains/ignastudio.com/public_html/.env
```

Create private backups:

```bash
DEPLOY_TS="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_DIR="/home/u935649387/deployment-backups/phase-5b2-${DEPLOY_TS}"
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

tar -C /home/u935649387/apps/igna-studio -czf "$BACKUP_DIR/storage-app.tar.gz" storage/app
tar -C /home/u935649387/domains/ignastudio.com/public_html -czf "$BACKUP_DIR/public-bridge-igna-app.tar.gz" igna-app
cp .env "$BACKUP_DIR/env.backup"
chmod 600 "$BACKUP_DIR/env.backup"
```

Create the database backup from hPanel or the approved Hostinger database tool without printing credentials in the shell transcript. Place the resulting private backup reference in `$BACKUP_DIR/database-backup-reference.txt`.

Verify backups:

```bash
test -s "$BACKUP_DIR/storage-app.tar.gz"
test -s "$BACKUP_DIR/public-bridge-igna-app.tar.gz"
test -s "$BACKUP_DIR/env.backup"
test -s "$BACKUP_DIR/database-backup-reference.txt"
shasum -a 256 "$BACKUP_DIR"/*.tar.gz "$BACKUP_DIR/env.backup" > "$BACKUP_DIR/checksums.sha256"
cat "$BACKUP_DIR/checksums.sha256"
```

Upload public artifact to `/home/u935649387/releases` and verify the SHA-256 against the release manifest:

```bash
mkdir -p /home/u935649387/releases
cd /home/u935649387/releases
RELEASE_COMMIT="$(cd /home/u935649387/apps/igna-studio && git ls-remote origin refs/heads/release/phase-5b2-2026q3 | awk '{print $1}')"
test -n "$RELEASE_COMMIT"
shasum -a 256 "igna-phase-5b2-public-${RELEASE_COMMIT}.tar.gz"
```

Enable maintenance mode:

```bash
cd /home/u935649387/apps/igna-studio
php artisan down --render="errors::503" || php artisan down
```

Update source:

```bash
git fetch origin release/phase-5b2-2026q3
git checkout release/phase-5b2-2026q3
git pull --ff-only origin release/phase-5b2-2026q3
git rev-parse HEAD
git status --short
```

```bash
RELEASE_COMMIT="$(git rev-parse origin/release/phase-5b2-2026q3)"
test "$(git rev-parse HEAD)" = "$RELEASE_COMMIT"
```

Install production PHP dependencies from the lockfile:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer audit --locked
```

Clear caches, migrate, and rebuild:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan migrate:status
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Replace only the public bridge files from the verified artifact:

```bash
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
RELEASE_TMP="/home/u935649387/releases/phase-5b2-public"
rm -rf "$RELEASE_TMP"
mkdir -p "$RELEASE_TMP"
tar -xzf "/home/u935649387/releases/igna-phase-5b2-public-${RELEASE_COMMIT}.tar.gz" -C "$RELEASE_TMP"

test "$PUBLIC_BRIDGE" = "/home/u935649387/domains/ignastudio.com/public_html/igna-app"
test -f "$PUBLIC_BRIDGE/index.php"
test -f "$PUBLIC_BRIDGE/.htaccess"

rsync -a --delete "$RELEASE_TMP/build/" "$PUBLIC_BRIDGE/build/"
for asset in favicon.ico favicon-16x16.png favicon-32x32.png apple-touch-icon.png android-chrome-192x192.png android-chrome-512x512.png site.webmanifest; do
    if [ -f "$RELEASE_TMP/$asset" ]; then
        cp "$RELEASE_TMP/$asset" "$PUBLIC_BRIDGE/$asset"
    fi
done
```

Do not overwrite the split-root `index.php` or `.htaccess` unless a reviewed bridge change explicitly requires it.

Exit maintenance mode and smoke test:

```bash
cd /home/u935649387/apps/igna-studio
php artisan up
curl -I https://ignastudio.com/
curl -I https://ignastudio.com/igna-app/favicon.ico
curl -I https://ignastudio.com/brand/favicon
tail -n 120 storage/logs/laravel.log
```

Record deployment:

```bash
{
    echo "phase=5b2"
    echo "branch=release/phase-5b2-2026q3"
    echo "commit=$(git rev-parse HEAD)"
    echo "backup_dir=$BACKUP_DIR"
    echo "deployed_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
} > "$BACKUP_DIR/deployment-record.txt"
cat "$BACKUP_DIR/deployment-record.txt"
```

## Manual Browser Smoke

- Homepage returns normally.
- Login works.
- Admin Dashboard, Projects, Proposals, Users, Team, Services, and Settings load.
- Superadministrator sees compact delete controls.
- Normal administrator does not see permanent deletion controls.
- Modals open and cancel only; do not execute production deletion during smoke.
- Used service and current-user deletion blocks render.
- No Laravel debug page, HTTP 500, or unexpected log error appears.
