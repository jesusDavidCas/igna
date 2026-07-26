#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

readonly PRODUCTION_APP_ROOT="/home/u935649387/apps/igna-studio"
readonly PRODUCTION_BRIDGE_ROOT="/home/u935649387/domains/ignastudio.com/public_html/igna-app"
readonly PRODUCTION_BACKUP_ROOT="/home/u935649387/backups/igna-studio"
readonly PRODUCTION_USER="u935649387"

usage() {
    cat <<'EOF'
IGNA Studio Hostinger release preflight (non-mutating)

Usage:
  scripts/deploy-hostinger.sh --preflight \
    --expected-commit FULL_40_CHARACTER_COMMIT \
    --backup-dir /home/u935649387/backups/igna-studio/2026Q3-TIMESTAMP \
    --backup-confirmed

This script intentionally performs no fetch, checkout, dependency installation,
migration, cache rebuild, or public-file synchronization. After it passes, use:
  docs/deployment/HOSTINGER_RELEASE_RUNBOOK_2026Q3.md
EOF
}

fail() {
    printf 'PRECHECK FAILED: %s\n' "$1" >&2
    exit 1
}

mode=""
expected_commit=""
backup_dir=""
backup_confirmed="false"
app_root="$PRODUCTION_APP_ROOT"
bridge_root="$PRODUCTION_BRIDGE_ROOT"
expected_user="$PRODUCTION_USER"

while (($# > 0)); do
    case "$1" in
        --preflight|--dry-run)
            mode="preflight"
            shift
            ;;
        --expected-commit)
            (($# >= 2)) || fail "--expected-commit requires a value"
            expected_commit="$2"
            shift 2
            ;;
        --backup-dir)
            (($# >= 2)) || fail "--backup-dir requires a value"
            backup_dir="$2"
            shift 2
            ;;
        --backup-confirmed)
            backup_confirmed="true"
            shift
            ;;
        --app-root)
            (($# >= 2)) || fail "--app-root requires a value"
            [[ "${IGNA_DEPLOY_TEST_MODE:-0}" == "1" ]] || fail "application root overrides are test-only"
            app_root="$2"
            shift 2
            ;;
        --bridge-root)
            (($# >= 2)) || fail "--bridge-root requires a value"
            [[ "${IGNA_DEPLOY_TEST_MODE:-0}" == "1" ]] || fail "bridge root overrides are test-only"
            bridge_root="$2"
            shift 2
            ;;
        --expected-user)
            (($# >= 2)) || fail "--expected-user requires a value"
            [[ "${IGNA_DEPLOY_TEST_MODE:-0}" == "1" ]] || fail "user overrides are test-only"
            expected_user="$2"
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            fail "unknown argument: $1"
            ;;
    esac
done

[[ "$mode" == "preflight" ]] || fail "--preflight is required"
[[ -n "$expected_commit" ]] || fail "expected release commit is required"
[[ "$expected_commit" =~ ^[0-9a-fA-F]{40}$ ]] || fail "expected release commit must be a full 40-character SHA"
[[ "$backup_confirmed" == "true" ]] || fail "explicit backup confirmation is required"
[[ -n "$backup_dir" ]] || fail "backup directory is required"

if [[ "${IGNA_DEPLOY_TEST_MODE:-0}" != "1" ]]; then
    [[ "$app_root" == "$PRODUCTION_APP_ROOT" ]] || fail "unexpected Laravel application root"
    [[ "$bridge_root" == "$PRODUCTION_BRIDGE_ROOT" ]] || fail "unexpected public bridge root"
    [[ "$backup_dir" == "$PRODUCTION_BACKUP_ROOT/"* ]] || fail "backup is outside the private production backup root"
fi

[[ "$bridge_root" != */public_html ]] || fail "parent public_html cannot be targeted"
[[ "$(basename "$bridge_root")" == "igna-app" ]] || fail "public bridge must be the dedicated igna-app directory"
[[ "$(id -un)" == "$expected_user" ]] || fail "unexpected operating-system user"
[[ -d "$app_root/.git" ]] || fail "Laravel root is not the expected Git worktree"
[[ -f "$app_root/artisan" && -f "$app_root/composer.json" ]] || fail "Laravel identity files are missing"
[[ -d "$bridge_root" && -f "$bridge_root/index.php" ]] || fail "public bridge identity is missing"

cd "$app_root"

[[ "$(git branch --show-current)" == "main" ]] || fail "production branch must be main"
[[ -z "$(git status --porcelain --untracked-files=normal)" ]] || fail "production worktree is dirty"
git cat-file -e "${expected_commit}^{commit}" 2>/dev/null || fail "expected release commit is unavailable"

if [[ "${IGNA_DEPLOY_TEST_MODE:-0}" != "1" ]]; then
    git show-ref --verify --quiet refs/remotes/origin/main || fail "origin/main is unavailable; run the runbook fetch step"
    [[ "$(git rev-parse origin/main)" == "$expected_commit" ]] || fail "origin/main does not equal the expected release commit"
    git merge-base --is-ancestor HEAD "$expected_commit" || fail "production HEAD cannot fast-forward to the release commit"
fi

[[ -d "$backup_dir" ]] || fail "confirmed backup directory does not exist"

for required_backup in \
    database-before.sql.gz \
    storage-app-before.tar.gz \
    public-bridge-before.tar.gz \
    deployment-record.txt \
    SHA256SUMS
do
    [[ -f "$backup_dir/$required_backup" ]] || fail "backup artifact is missing: $required_backup"
done

(
    cd "$backup_dir"
    sha256sum -c SHA256SUMS
) || fail "backup checksum verification failed"

previous_commit="$(git rev-parse HEAD)"

cat <<EOF
PRECHECK PASSED - NO PRODUCTION MUTATION PERFORMED
application_root=$app_root
public_bridge=$bridge_root
previous_commit=$previous_commit
expected_release_commit=$expected_commit
verified_backup=$backup_dir
deployment_runbook=$app_root/docs/deployment/HOSTINGER_RELEASE_RUNBOOK_2026Q3.md
rollback_runbook=$app_root/docs/deployment/HOSTINGER_ROLLBACK_RUNBOOK_2026Q3.md
EOF
