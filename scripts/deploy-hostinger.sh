#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

git pull --ff-only origin main

composer install --no-dev --optimize-autoloader

if command -v npm >/dev/null 2>&1; then
    npm ci
    npm run build
fi

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment finished."
