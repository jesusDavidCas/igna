# IGNA Studio Hostinger Rollback Runbook - 2026 Q3

Status: planning artifact. Execute only after human incident-lead approval.

Production roots:

- Laravel: `/home/u935649387/apps/igna-studio`
- Public bridge:
  `/home/u935649387/domains/ignastudio.com/public_html/igna-app`
- Private backup root: `/home/u935649387/backups/igna-studio`

This release introduces exactly:

1. `2026_07_25_000100_add_client_document_metadata_to_ticket_files.php`
2. `2026_07_25_000200_add_review_download_and_stage_rollback_metadata.php`
3. `2026_07_26_000100_add_auth_session_version_to_users.php`

Never run broad `php artisan migrate:rollback` when unrelated later migrations
may exist. Never restore a database or force-reset code without explicit human
confirmation.

## 1. Declare The Incident And Freeze Changes

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
cd /home/u935649387/apps/igna-studio
php artisan down --retry=60
git status --short
git branch --show-current
git rev-parse HEAD
php artisan migrate:status
```

Stop queue workers or external job producers only through Hostinger's approved
operational procedure. Do not improvise service commands.

**STOP CONDITION**

- Production repository is dirty or has unexplained files.
- Backup directory cannot be identified.
- Another migration was deployed after the Q3 release.

Escalate rather than overwriting unknown production state.

## 2. Select And Validate The Backup

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

Enter the private backup directory recorded during deployment:

```bash
read -r -p "Private Q3 backup directory: " BACKUP_DIR
case "$BACKUP_DIR" in
    /home/u935649387/backups/igna-studio/2026Q3-*) ;;
    *) printf 'Invalid backup directory\n' >&2; exit 1 ;;
esac
test -d "$BACKUP_DIR"
test -f "$BACKUP_DIR/deployment-record.txt"
test -f "$BACKUP_DIR/database-before.sql.gz"
test -f "$BACKUP_DIR/storage-app-before.tar.gz"
test -f "$BACKUP_DIR/public-bridge-before.tar.gz"
cd "$BACKUP_DIR"
sha256sum -c SHA256SUMS
PREVIOUS_COMMIT="$(sed -n 's/^previous_commit=//p' deployment-record.txt)"
RELEASE_COMMIT="$(sed -n 's/^release_commit=//p' deployment-record.txt)"
test -n "$PREVIOUS_COMMIT"
test -n "$RELEASE_COMMIT"
```

**EXPECTED RESULT**

All backup checksums pass and both commit IDs are known.

## 3. Choose Rollback Mode

**HUMAN DECISION**

Use one mode:

| Mode | Use when | Data effect |
| --- | --- | --- |
| Code-only rollback | Migration/data are sound; application code/assets are faulty but old code can tolerate additive schema. | Keeps all production data and new columns/tables. |
| Code plus targeted migration rollback | Release is very recent, no meaningful new review/audit or authentication-session state exists, all three migration files are the known latest changes, and down methods are safe for current data. | Deletes Q3 review/audit metadata and audit table, and removes the session-version column. |
| Emergency database restore | Partial DDL, corruption, unexpected migration state, or rollback cannot preserve integrity. | Restores database to pre-release point; post-backup writes are lost unless separately reconciled. |

When uncertain, preserve data, keep maintenance mode active, and escalate.

## 4. Code-Only Rollback

The additive schema is compatible with the pre-release code because old code
ignores the added columns and table.

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN CONFIRMATION REQUIRED**

Confirm Git is clean and `PREVIOUS_COMMIT` is the recorded pre-release commit:

```bash
APP_ROOT="/home/u935649387/apps/igna-studio"
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
cd "$APP_ROOT"
test -z "$(git status --porcelain)"
test "$(git rev-parse HEAD)" = "$RELEASE_COMMIT"
git show -s --format='%H %s' "$PREVIOUS_COMMIT"
```

Emergency containment may rewind the clean local production `main`:

```bash
git reset --hard "$PREVIOUS_COMMIT"
test "$(git rev-parse HEAD)" = "$PREVIOUS_COMMIT"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

This is justified only for a clean production repository under maintenance. It
temporarily leaves local `main` behind `origin/main`. Immediately open a GitHub
revert PR after service restoration so production can return to normal
fast-forward deployment.

Restore builds:

```bash
test -d "$BACKUP_DIR/app-build-before"
test -d "$BACKUP_DIR/public-build-before"
mv "$APP_ROOT/public/build" "$BACKUP_DIR/failed-app-build-$RELEASE_COMMIT"
mv "$BACKUP_DIR/app-build-before" "$APP_ROOT/public/build"
mv "$PUBLIC_BRIDGE/build" "$BACKUP_DIR/failed-public-build-$RELEASE_COMMIT"
mv "$BACKUP_DIR/public-build-before" "$PUBLIC_BRIDGE/build"
test -f "$APP_ROOT/public/build/manifest.json"
test -f "$PUBLIC_BRIDGE/build/manifest.json"
```

Rebuild caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Proceed to smoke tests in section 7.

## 5. Code Plus Targeted Migration Rollback

Use only when:

- all three Q3 migrations are the latest production migrations;
- no unrelated migration has run after them;
- humans accept loss of Q3 audit/review/download/rejection/supersession data and
  removal of the authentication-session version state;
- a verified database backup exists.

Rollback schema while release code and migration files are still present, in
reverse order:

**Location: HOSTINGER LARAVEL ROOT**

```bash
cd /home/u935649387/apps/igna-studio
test "$(git rev-parse HEAD)" = "$RELEASE_COMMIT"
php artisan migrate:status
php artisan migrate:rollback --path=database/migrations/2026_07_26_000100_add_auth_session_version_to_users.php --force
php artisan migrate:rollback --path=database/migrations/2026_07_25_000200_add_review_download_and_stage_rollback_metadata.php --force
php artisan migrate:rollback --path=database/migrations/2026_07_25_000100_add_client_document_metadata_to_ticket_files.php --force
php artisan migrate:status
```

**STOP CONDITION**

- Any migration is not in the expected latest state.
- Laravel refuses targeted rollback.
- DDL fails or only partially completes.

On any stop condition, do not keep experimenting. Use emergency database restore.

After successful targeted rollback, perform the code-only rollback in section 4.

## 6. Emergency Database Restore

This procedure loses database writes made after the backup. The incident lead,
data owner, and deployment operator must explicitly approve it.

Prefer Hostinger hPanel's tested private restore mechanism. If the production
team uses the reviewed private MySQL option file:

**Location: HOSTINGER LARAVEL ROOT**

```bash
MYSQL_BACKUP_OPTIONS="/home/u935649387/.config/igna-studio/mysql-backup.cnf"
test -r "$MYSQL_BACKUP_OPTIONS"
test "$(stat -c '%a' "$MYSQL_BACKUP_OPTIONS")" = "600"
test -s "$BACKUP_DIR/database-before.sql.gz"
read -rsp "Hostinger database name to restore: " IGNA_DB_NAME
printf '\n'
printf 'Database restore will overwrite current production data. Type RESTORE to continue: '
read -r RESTORE_CONFIRMATION
test "$RESTORE_CONFIRMATION" = "RESTORE"
gzip -cd "$BACKUP_DIR/database-before.sql.gz" | mysql --defaults-extra-file="$MYSQL_BACKUP_OPTIONS" "$IGNA_DB_NAME"
unset IGNA_DB_NAME RESTORE_CONFIRMATION
```

Do not put database credentials on the command line or in shell history.

Restore private storage only if the release changed/corrupted files and the data
owner accepts losing post-backup uploads:

```bash
APP_ROOT="/home/u935649387/apps/igna-studio"
printf 'Storage restore can remove post-backup uploads. Type RESTORE-STORAGE to continue: '
read -r STORAGE_CONFIRMATION
test "$STORAGE_CONFIRMATION" = "RESTORE-STORAGE"
mv "$APP_ROOT/storage/app" "$BACKUP_DIR/failed-storage-app"
mkdir "$APP_ROOT/storage/app"
tar -xzf "$BACKUP_DIR/storage-app-before.tar.gz" -C "$APP_ROOT"
unset STORAGE_CONFIRMATION
```

Restore the public bridge snapshot only when atomic build restoration is
impossible and after validating the exact destination:

```bash
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
test "$PUBLIC_BRIDGE" = "/home/u935649387/domains/ignastudio.com/public_html/igna-app"
printf 'Full bridge restore affects the live public entry. Type RESTORE-BRIDGE to continue: '
read -r BRIDGE_CONFIRMATION
test "$BRIDGE_CONFIRMATION" = "RESTORE-BRIDGE"
mv "$PUBLIC_BRIDGE" "$BACKUP_DIR/failed-public-bridge"
mkdir "$PUBLIC_BRIDGE"
tar -xzf "$BACKUP_DIR/public-bridge-before.tar.gz" -C "$(dirname "$PUBLIC_BRIDGE")"
unset BRIDGE_CONFIRMATION
```

Reinstall old code dependencies and rebuild caches as in section 4.

## 7. Rollback Smoke Tests

**Location: HOSTINGER LARAVEL ROOT**

```bash
cd /home/u935649387/apps/igna-studio
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor > /dev/null
test -w storage
test -w bootstrap/cache
test -f public/build/manifest.json
test -f /home/u935649387/domains/ignastudio.com/public_html/igna-app/build/manifest.json
php artisan up
```

**Location: LOCAL MAC**

```bash
for rollback_path in / /login /tracking /robots.txt /sitemap.xml /up; do
    rollback_status="$(curl -sS -o /dev/null -w '%{http_code}' "https://ignastudio.com${rollback_path}")"
    printf '%s %s\n' "$rollback_path" "$rollback_status"
done
curl -sS -o /dev/null -w '%{http_code}\n' https://ignastudio.com/build/manifest.json
```

**HUMAN ACTION - BROWSER**

With designated QA accounts:

- verify login and admin dashboard;
- verify one QA ticket and authorized client boundary;
- verify tracking without submitting data;
- verify one QA public proposal and authorized PDF;
- verify no missing assets, console errors, or 500 responses.

If smoke tests fail, return to maintenance mode and escalate. Do not repeatedly
toggle schema or restore different backups without an incident plan.

## 8. Reconcile GitHub And Production

After emergency local rewind, production cannot safely fast-forward until GitHub
contains a reviewed revert or forward-fix commit.

**Location: GITHUB UI**

**HUMAN ACTION**

1. Create a revert or forward-fix branch from current `main`.
2. Review and merge through a pull request.
3. Rerun release gates.

**Location: HOSTINGER LARAVEL ROOT**

After GitHub has a reviewed descendant of the production commit:

```bash
cd /home/u935649387/apps/igna-studio
test -z "$(git status --porcelain)"
test "$(git branch --show-current)" = "main"
git fetch origin main
git merge --ff-only origin/main
```

Never force-push the release or main branch.

## 9. Incident Record

Record privately:

- trigger and UTC timeline;
- release and previous commit IDs;
- rollback mode and approvers;
- exact migration state before/after;
- backup directory identifier and checksum result;
- whether post-backup data was lost/reconciled;
- smoke-test and monitoring result;
- GitHub revert/forward-fix PR;
- follow-up owners.

Never record passwords, tokens, private keys, database credentials, `.env`
contents, cookies, signed URLs, or client data.
