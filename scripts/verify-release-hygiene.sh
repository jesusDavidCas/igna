#!/usr/bin/env bash

# Reports file paths only so a release check never echoes suspected secret values.
set -euo pipefail

repository_root="$(git rev-parse --show-toplevel)"
cd "$repository_root"

if ! git diff --check >/dev/null; then
    echo "Release hygiene failed: whitespace errors are present." >&2
    exit 1
fi

if git ls-files | grep -Eq '(^|/)\.env($|[._-](backup|local|production|staging)([._-]|$))'; then
    echo "Release hygiene failed: tracked environment material is present." >&2
    exit 1
fi

patterns=(
    '-----BEGIN (RSA |EC |OPENSSH |PGP )?PRIVATE KEY-----'
    'AKIA[0-9A-Z]{16}'
    'AIza[0-9A-Za-z_-]{35}'
    'gh[pousr]_[0-9A-Za-z_]{20,}'
    'github_pat_[0-9A-Za-z_]{20,}'
    'xox[baprs]-[0-9A-Za-z-]{20,}'
    'sk-[0-9A-Za-z_-]{20,}'
)

for pattern in "${patterns[@]}"; do
    matches="$(git grep -I -l -E -e "$pattern" -- ':!composer.lock' ':!package-lock.json' || true)"

    if [[ -n "$matches" ]]; then
        echo "Release hygiene failed: possible credential material in tracked file(s):" >&2
        printf '%s\n' "$matches" >&2
        exit 1
    fi
done

echo "Release hygiene passed: no tracked environment artifacts or high-signal credential patterns found."
