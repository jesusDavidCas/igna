# IGNA Studio Hostinger Release Runbook - 2026 Q3

Status: **SOURCE READY - HOSTINGER PREFLIGHT AND HUMAN APPROVAL REQUIRED**

This runbook is tailored to:

- local repository:
  `/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform`
- Hostinger Laravel root:
  `/home/u935649387/apps/igna-studio`
- Hostinger public bridge:
  `/home/u935649387/domains/ignastudio.com/public_html/igna-app`
- Git remote: `git@github.com:jesusDavidCas/igna.git`

Source-level HIGH findings are closed by the 2026 Q3 security remediation.
Do not execute production mutation steps until the Hostinger read-only preflight,
the non-mutating script preflight, backup verification, and explicit human
approval all pass.

`scripts/deploy-hostinger.sh` is a non-mutating preflight. It is not a
deployment command and cannot update code, dependencies, schema, caches, or
public files.

Conventions used below:

- **HUMAN ACTION**: an authorized person executes or approves.
- **CODEX VERIFICATION**: read-only assistance that Codex may perform.
- **STOP CONDITION**: do not continue when true.
- **EXPECTED RESULT**: required evidence before the next step.
- **ROLLBACK POINT**: state that can be restored.

## 1. Deployment Prerequisites

**Location: LOCAL MAC**

**HUMAN ACTION**

Confirm:

- high security findings are closed in reviewed commits;
- `composer audit --locked` has no reachable unmitigated high/critical advisory;
- `npm audit --omit=dev` is clean;
- full and focused tests pass;
- current browser and PDF QA pass;
- the three Q3 audit/runbook documents are reviewed;
- a Hostinger maintenance window and QA operator are available.

Run:

```bash
cd "/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform"
git fetch --prune origin
git status --short
git diff --check
composer audit --locked
npm audit --omit=dev
php artisan test
npm run build
```

**STOP CONDITION**

- Any tracked or unexplained local change.
- Any failed test/build/audit.
- Any `.env`, backup, screenshot, QA PDF, Graphify output, or local evidence in
  the proposed release diff.

**EXPECTED RESULT**

Clean tracked worktree and all gates green.

## 2. Release Identification

**Location: LOCAL MAC**

The current graph allows the Q3 release branch to fast-forward from `1824b89` to
the audited Phase 2 checkpoint `90301e7`. Security remediation commits must be
added before release.

**HUMAN ACTION**

```bash
cd "/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform"
git status --short
git switch release/igna-workflow-proposals-2026q3
git merge --ff-only 90301e7
git log --oneline --decorate --graph origin/main..HEAD
```

Apply reviewed security remediation commits to this release branch through the
normal reviewed development flow. Then:

```bash
php artisan test
npm run build
composer audit --locked
npm audit --omit=dev
RELEASE_COMMIT="$(git rev-parse HEAD)"
printf 'Release commit: %s\n' "$RELEASE_COMMIT"
```

**STOP CONDITION**

- Fast-forward fails.
- Release branch does not contain `1824b89` and `90301e7`.
- Security fixes are absent.

**EXPECTED RESULT**

One reviewed release commit identity stored in `RELEASE_COMMIT`.

**ROLLBACK POINT**

Local branch remains recoverable through Git history; no force push is allowed.

## 3. GitHub Merge Verification

**Location: LOCAL MAC**

**HUMAN ACTION**

```bash
git diff --check
git diff --stat origin/main...HEAD
git log --oneline origin/main..HEAD
git push -u origin release/igna-workflow-proposals-2026q3
```

**Location: GITHUB UI**

**HUMAN ACTION**

1. Open a pull request:
   `release/igna-workflow-proposals-2026q3` -> `main`.
2. Review every changed file and all CI/security checks.
3. Confirm no local-only path appears.
4. Merge only after approvals and required checks.

**Location: LOCAL MAC**

```bash
git fetch --prune origin
RELEASE_COMMIT="$(git rev-parse origin/main)"
git merge-base --is-ancestor 90301e7 "$RELEASE_COMMIT"
git log -1 --format='%H %s' origin/main
```

**STOP CONDITION**

- PR checks fail.
- `origin/main` does not contain the approved release.
- The recorded commit differs from the approved GitHub merge commit.

**EXPECTED RESULT**

`RELEASE_COMMIT` is the exact approved `origin/main` commit.

## 4. Production Read-Only Preflight

**Location: HOSTINGER LARAVEL ROOT**

Connect without sharing a password:

```bash
ssh -p 65002 u935649387@212.85.28.79
cd /home/u935649387/apps/igna-studio
```

**HUMAN ACTION**

Run read-only checks:

```bash
pwd
git status --short
git branch --show-current
git rev-parse HEAD
git log -5 --oneline
git remote -v
git fetch --prune origin main
git rev-parse origin/main
php -v
composer --version
node --version
npm --version
php artisan about
php artisan migrate:status
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config("database.default"), PHP_EOL;'
php -m
ls -ld storage bootstrap/cache
test -w storage
test -w bootstrap/cache
du -sh storage
ls -ld /home/u935649387/domains/ignastudio.com/public_html/igna-app
test -f /home/u935649387/domains/ignastudio.com/public_html/igna-app/index.php
test -f /home/u935649387/domains/ignastudio.com/public_html/igna-app/.htaccess
test -f /home/u935649387/domains/ignastudio.com/public_html/igna-app/build/manifest.json
readlink /home/u935649387/domains/ignastudio.com/public_html/igna-app/storage
```

Do not print `.env`, environment variables, credentials, cookies, or signed URLs.

**STOP CONDITION**

- Repository is dirty or not on `main`.
- PHP is below 8.4.
- Required extensions are missing: `pdo_mysql`, `mbstring`, `openssl`,
  `fileinfo`, `gd`, `intl`, or `zip`.
- `storage` or `bootstrap/cache` is not writable.
- Database driver is not the expected reviewed production driver.
- Public bridge entry/build/storage state differs from the reviewed topology.

**EXPECTED RESULT**

Human records only non-secret versions, branch, commit, migration names, driver,
extension availability, permissions, and bridge checks.

## 5. Secure Backup

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
set -euo pipefail
APP_ROOT="/home/u935649387/apps/igna-studio"
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
BACKUP_ROOT="/home/u935649387/backups/igna-studio"
DEPLOY_STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_DIR="$BACKUP_ROOT/2026Q3-$DEPLOY_STAMP"
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_ROOT" "$BACKUP_DIR"
cd "$APP_ROOT"
PREVIOUS_COMMIT="$(git rev-parse HEAD)"
printf 'previous_commit=%s\n' "$PREVIOUS_COMMIT" > "$BACKUP_DIR/deployment-record.txt"
git branch --show-current >> "$BACKUP_DIR/deployment-record.txt"
php artisan migrate:status > "$BACKUP_DIR/migrations-before.txt"
install -m 600 .env "$BACKUP_DIR/.env"
tar -C "$APP_ROOT" -czf "$BACKUP_DIR/storage-app-before.tar.gz" storage/app
tar -C "$(dirname "$PUBLIC_BRIDGE")" -czf "$BACKUP_DIR/public-bridge-before.tar.gz" "$(basename "$PUBLIC_BRIDGE")"
sha256sum "$PUBLIC_BRIDGE/index.php" > "$BACKUP_DIR/public-index-before.sha256"
```

Database backup requires a human-managed MySQL option file. It must be private,
outside `public_html`, readable only by the account, and must not be printed.

```bash
MYSQL_BACKUP_OPTIONS="/home/u935649387/.config/igna-studio/mysql-backup.cnf"
test -r "$MYSQL_BACKUP_OPTIONS"
test "$(stat -c '%a' "$MYSQL_BACKUP_OPTIONS")" = "600"
read -rsp "Hostinger database name: " IGNA_DB_NAME
printf '\n'
mysqldump --defaults-extra-file="$MYSQL_BACKUP_OPTIONS" --single-transaction --quick --routines --triggers --events --hex-blob "$IGNA_DB_NAME" | gzip -9 > "$BACKUP_DIR/database-before.sql.gz"
unset IGNA_DB_NAME
test -s "$BACKUP_DIR/database-before.sql.gz"
sha256sum "$BACKUP_DIR/database-before.sql.gz" "$BACKUP_DIR/storage-app-before.tar.gz" "$BACKUP_DIR/public-bridge-before.tar.gz" > "$BACKUP_DIR/SHA256SUMS"
```

If the option file is not an existing trusted production mechanism, use
Hostinger hPanel's private database export instead and save it in
`$BACKUP_DIR/database-before.sql.gz`. Never put a backup under `public_html`.

**STOP CONDITION**

Any backup or checksum is missing/empty, or restore authority is unavailable.

**EXPECTED RESULT**

Private, checksummed code identity, migration list, environment copy, database,
storage, and public bridge backups.

**ROLLBACK POINT**

`$BACKUP_DIR`.

Run the fail-closed, non-mutating preflight after the backup exists and
`origin/main` has been fetched:

```bash
cd /home/u935649387/apps/igna-studio
read -r -p "Approved full release commit: " RELEASE_COMMIT
test "$RELEASE_COMMIT" = "$(git rev-parse origin/main)"
scripts/deploy-hostinger.sh \
    --preflight \
    --expected-commit "$RELEASE_COMMIT" \
    --backup-dir "$BACKUP_DIR" \
    --backup-confirmed
```

**STOP CONDITION**

The script does not print `PRECHECK PASSED - NO PRODUCTION MUTATION PERFORMED`.

## 6. Maintenance Mode Decision

**Location: HOSTINGER LARAVEL ROOT**

The release alters `users`, `ticket_files`, and `ticket_stage_events`, and adds a
foreign-key audit table. Use a short maintenance window.

**HUMAN ACTION**

```bash
cd /home/u935649387/apps/igna-studio
DEPLOYMENT_COMPLETE=0
leave_maintenance_on_failure() {
    if [ "$DEPLOYMENT_COMPLETE" -ne 1 ]; then
        php artisan up || true
    fi
}
trap leave_maintenance_on_failure EXIT
php artisan down --retry=60
```

**EXPECTED RESULT**

Maintenance mode is active. The shell trap will attempt to exit maintenance after
a controlled failure.

## 7. Code Update

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
APP_ROOT="/home/u935649387/apps/igna-studio"
cd "$APP_ROOT"
test -z "$(git status --porcelain)"
test "$(git branch --show-current)" = "main"
git fetch origin main
RELEASE_COMMIT="$(git rev-parse origin/main)"
git merge --ff-only "$RELEASE_COMMIT"
test "$(git rev-parse HEAD)" = "$RELEASE_COMMIT"
printf 'release_commit=%s\n' "$RELEASE_COMMIT" >> "$BACKUP_DIR/deployment-record.txt"
```

**STOP CONDITION**

Dirty repository, wrong branch, non-fast-forward update, or commit mismatch.

**EXPECTED RESULT**

Production `main` is clean at exact approved `RELEASE_COMMIT`.

**ROLLBACK POINT**

`PREVIOUS_COMMIT` recorded in `$BACKUP_DIR/deployment-record.txt`.

## 8. Composer Dependencies

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
cd /home/u935649387/apps/igna-studio
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer audit --locked
php artisan about
```

Do not use `composer update`.

**STOP CONDITION**

Install/audit failure, platform requirement failure, or unexpected plugin/script.

**EXPECTED RESULT**

Production vendor tree matches the reviewed `composer.lock`.

## 9. Frontend Build And Assets

The server's Node capability was not verified during this audit. Use a locally
built, checksummed artifact from the reviewed lock file.

**Location: HOSTINGER ARTIFACT ROOT**

**HUMAN ACTION**

```bash
install -d -m 700 /home/u935649387/releases/igna-studio
```

**STOP CONDITION**

The private artifact directory cannot be created with mode `700`.

**Location: LOCAL MAC**

**HUMAN ACTION**

```bash
cd "/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform"
git fetch --prune origin
RELEASE_COMMIT="$(git rev-parse origin/main)"
git switch --detach "$RELEASE_COMMIT"
npm ci --ignore-scripts
npm run build
ARTIFACT_DIR="output/release-artifacts/$RELEASE_COMMIT"
mkdir -p "$ARTIFACT_DIR"
tar -C public/build -czf "$ARTIFACT_DIR/public-build.tar.gz" .
shasum -a 256 "$ARTIFACT_DIR/public-build.tar.gz" > "$ARTIFACT_DIR/public-build.sha256"
scp -P 65002 "$ARTIFACT_DIR/public-build.tar.gz" "$ARTIFACT_DIR/public-build.sha256" u935649387@212.85.28.79:/home/u935649387/releases/igna-studio/
git switch release/igna-workflow-proposals-2026q3
```

**Location: HOSTINGER LARAVEL ROOT**

```bash
APP_ROOT="/home/u935649387/apps/igna-studio"
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
ARTIFACT_ROOT="/home/u935649387/releases/igna-studio"
ARTIFACT="$ARTIFACT_ROOT/public-build.tar.gz"
ARTIFACT_SHA="$ARTIFACT_ROOT/public-build.sha256"
EXPECTED_SHA="$(awk '{print $1}' "$ARTIFACT_SHA")"
ACTUAL_SHA="$(sha256sum "$ARTIFACT" | awk '{print $1}')"
test "$EXPECTED_SHA" = "$ACTUAL_SHA"
APP_BUILD_NEXT="$APP_ROOT/public/build.next-$RELEASE_COMMIT"
BRIDGE_BUILD_NEXT="$PUBLIC_BRIDGE/build.next-$RELEASE_COMMIT"
mkdir "$APP_BUILD_NEXT" "$BRIDGE_BUILD_NEXT"
tar -xzf "$ARTIFACT" -C "$APP_BUILD_NEXT"
cp -a "$APP_BUILD_NEXT/." "$BRIDGE_BUILD_NEXT/"
test -f "$APP_BUILD_NEXT/manifest.json"
test -f "$BRIDGE_BUILD_NEXT/manifest.json"
```

Do not synchronize or delete the parent `public_html`.

**STOP CONDITION**

Checksum mismatch, missing manifest, or either destination resolves outside its
exact reviewed root.

**ROLLBACK POINT**

Current live build directories remain untouched until step 12.

## 10. Database Migration

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
cd /home/u935649387/apps/igna-studio
php artisan migrate:status
php artisan migrate --force
php artisan migrate:status
```

Expected new migrations, in order:

1. `2026_07_25_000100_add_client_document_metadata_to_ticket_files`
2. `2026_07_25_000200_add_review_download_and_stage_rollback_metadata`
3. `2026_07_26_000100_add_auth_session_version_to_users`

**STOP CONDITION**

Migration error, unexpected migration, lock timeout, or any expected migration
not marked Ran.

**EXPECTED RESULT**

All three additive migrations are recorded as ran. Existing users have
`auth_session_version = 1`; sessions created before this release are expected to
require a fresh login because they do not carry a version marker.

**ROLLBACK POINT**

Database backup. Do not run broad `migrate:rollback`.

## 11. Laravel Cache Optimization

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
cd /home/u935649387/apps/igna-studio
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan about
```

**STOP CONDITION**

Any cache command fails or `about` reports an unexpected environment/runtime.

**EXPECTED RESULT**

Config, routes, and views are cached under the release code.

## 12. Public Bridge Synchronization

**Location: HOSTINGER PUBLIC BRIDGE**

Only the `build` directory is replaced for this release. The specialized
split-root `index.php`, `.htaccess`, static files, and storage link are preserved.

**HUMAN ACTION**

```bash
APP_ROOT="/home/u935649387/apps/igna-studio"
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
test "$PUBLIC_BRIDGE" = "/home/u935649387/domains/ignastudio.com/public_html/igna-app"
sha256sum -c "$BACKUP_DIR/public-index-before.sha256"
mv "$APP_ROOT/public/build" "$BACKUP_DIR/app-build-before"
mv "$APP_BUILD_NEXT" "$APP_ROOT/public/build"
mv "$PUBLIC_BRIDGE/build" "$BACKUP_DIR/public-build-before"
mv "$BRIDGE_BUILD_NEXT" "$PUBLIC_BRIDGE/build"
test -f "$APP_ROOT/public/build/manifest.json"
test -f "$PUBLIC_BRIDGE/build/manifest.json"
sha256sum -c "$BACKUP_DIR/public-index-before.sha256"
```

**STOP CONDITION**

Index hash changes, manifest missing, or a command targets the parent
`public_html`.

**EXPECTED RESULT**

Private and bridge builds match the reviewed artifact; entrypoint is unchanged.

**ROLLBACK POINT**

`$BACKUP_DIR/app-build-before` and `$BACKUP_DIR/public-build-before`.

## 13. Storage And Permissions Verification

**Location: HOSTINGER LARAVEL ROOT**

**HUMAN ACTION**

```bash
APP_ROOT="/home/u935649387/apps/igna-studio"
PUBLIC_BRIDGE="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
test -w "$APP_ROOT/storage"
test -w "$APP_ROOT/bootstrap/cache"
test -L "$PUBLIC_BRIDGE/storage"
test "$(readlink "$PUBLIC_BRIDGE/storage")" = "$APP_ROOT/storage/app/public"
find "$PUBLIC_BRIDGE" -maxdepth 1 -mindepth 1 -printf '%f\n' | sort
```

Do not change permissions during deployment. A missing/wrong storage link requires
separate human review; do not overwrite a real directory.

**STOP CONDITION**

Unwritable private paths, wrong storage link, or private Laravel directory in the
bridge.

## 14. Smoke Tests

**Location: HOSTINGER LARAVEL ROOT**

Before exiting maintenance:

```bash
php artisan migrate:status
php artisan route:list --except-vendor > /dev/null
test -f public/build/manifest.json
```

Exit maintenance for HTTP/browser checks:

```bash
php artisan up
```

**Location: LOCAL MAC**

```bash
for smoke_path in / /login /tracking /robots.txt /sitemap.xml /up; do
    smoke_status="$(curl -sS -o /dev/null -w '%{http_code}' "https://ignastudio.com${smoke_path}")"
    printf '%s %s\n' "$smoke_path" "$smoke_status"
done
curl -sSI https://ignastudio.com/ | grep -Evi '^set-cookie:'
curl -sS -o /dev/null -w '%{http_code}\n' https://ignastudio.com/build/manifest.json
```

**HUMAN ACTION - BROWSER**

Using designated QA accounts and records:

- log in as admin; open ticket list/detail and proposal list/create/edit/show;
- verify stage complete/reopen controls without changing real client data;
- verify file visibility and reviewed client-document lifecycle on a QA ticket;
- log in as QA client; verify own ticket/document status and wrong-client denial;
- verify public request and tracking pages render without submitting real data;
- verify a QA public proposal and authorized proposal PDF;
- verify assets, mobile width, console, and network have no errors.

**STOP CONDITION**

Any 500, login failure, missing asset, cross-client access, broken PDF, or
workflow corruption.

**EXPECTED RESULT**

All non-destructive HTTP and QA browser checks pass.

## 15. Logs And Monitoring

**Location: HOSTINGER LARAVEL ROOT**

Do not copy client-bearing logs into tickets or chat.

```bash
cd /home/u935649387/apps/igna-studio
LOG_FILE="storage/logs/laravel.log"
test -f "$LOG_FILE"
tail -n 500 "$LOG_FILE" | grep -Ec 'CRITICAL|EMERGENCY|ALERT|production.ERROR' || true
```

**HUMAN ACTION**

Privately inspect any nonzero matches and Hostinger PHP/web error logs. Monitor
HTTP 5xx, login, queue/mail, upload, PDF, and latency for at least 30 minutes.

## 16. Exit Maintenance Mode

Maintenance was exited before smoke tests. Complete the trap only after all
checks pass:

```bash
cd /home/u935649387/apps/igna-studio
php artisan up
DEPLOYMENT_COMPLETE=1
trap - EXIT
```

**EXPECTED RESULT**

Application is live and the failure trap is removed.

## 17. Rollback Triggers

Start rollback on any of:

- failed/partial migration;
- login unavailable;
- sustained critical 500 errors;
- cross-client or public file disclosure;
- missing frontend assets;
- proposal PDF failure affecting operations;
- corrupted ticket/proposal workflow;
- unexpected private path in the public bridge;
- unrecoverable mail/queue failure.

Human incident lead decides between code-only rollback, code plus targeted
migration rollback, or emergency database restore.

## 18. Rollback Procedure

Use `docs/deployment/HOSTINGER_ROLLBACK_RUNBOOK_2026Q3.md`.

Do not use broad `php artisan migrate:rollback`. Do not force-reset a dirty
production repository. Database restore requires explicit human confirmation.

## 19. Post-Deployment Record

**Location: HOSTINGER LARAVEL ROOT**

```bash
cd /home/u935649387/apps/igna-studio
printf 'completed_at=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$BACKUP_DIR/deployment-record.txt"
git rev-parse HEAD >> "$BACKUP_DIR/deployment-record.txt"
php artisan migrate:status > "$BACKUP_DIR/migrations-after.txt"
sha256sum public/build/manifest.json /home/u935649387/domains/ignastudio.com/public_html/igna-app/build/manifest.json > "$BACKUP_DIR/build-manifests-after.sha256"
chmod 600 "$BACKUP_DIR/deployment-record.txt" "$BACKUP_DIR/migrations-before.txt" "$BACKUP_DIR/migrations-after.txt" "$BACKUP_DIR/build-manifests-after.sha256"
```

**Location: GITHUB UI**

Record:

- approved PR and merge commit;
- deploy operator and UTC time;
- backup directory identifier, not contents;
- migration names;
- smoke-test result;
- accepted risks and follow-up owners;
- monitoring/rollback outcome.

No password, token, key, client data, cookie, signed URL, or `.env` content may be
recorded.
