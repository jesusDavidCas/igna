# IGNA Studio Platform

IGNA Studio Platform is a lightweight Laravel business platform for IGNA Studio. It combines a bilingual public website, public request intake, ticket/project tracking, admin backoffice, client access, blog publishing, team credentials, protected file delivery, proposal generation, PDF export, WhatsApp proposal sharing, and email notifications.

The system is intentionally simple: Laravel owns the business logic, Blade renders the interface, Tailwind CSS handles styling, MySQL stores operational data, and private Laravel storage currently stores uploaded files while Google Drive remains available as a future storage backend.

## Current Production Shape

```text
Domain:              https://ignastudio.com
Laravel app:         /home/u935649387/apps/igna-studio
Public web folder:   /home/u935649387/domains/ignastudio.com/public_html/igna-app
Database:            Hostinger MySQL
Sender email:        support@ignastudio.com
```

Important: the Laravel application is outside the public web root. Only the public entry files and built assets should be exposed through Hostinger's `public_html/igna-app` folder.

## Documentation

Start here:

- [Project Manual](docs/project-manual.md): complete system explanation and operating guide.
- [Database Reference](docs/database-reference.md): main tables, relationships, and lifecycle data.
- [Operations Runbook](docs/operations-runbook.md): local setup, deployment, cache clearing, asset sync, backups, and common fixes.
- [Security And Storage](docs/security-and-storage.md): roles, file visibility, credential protection, email, upload limits, and Google Drive notes.
- [System Architecture](docs/system-architecture.md): high-level runtime shape.
- [Hostinger Deployment Guide](docs/hostinger-deployment.md): step-by-step deployment.
- [Demo Database Import](docs/import-demo-database.md): temporary demo data import process.
- [Technical Documentation](docs/technical-documentation.md): previous technical audit documentation.

## Main Features

- Bilingual Spanish/English homepage.
- Public service request form.
- Automatic ticket code generation.
- Ticket stage timeline with current/completed/reopened states.
- Admin dashboard for services, tickets, files, blog, users, settings, team, credentials, and proposals.
- Client area called My Services.
- Blog with draft/published workflow and sanitized HTML.
- Service-specific deliverables inherited by tickets.
- Protected credential previews with watermarking.
- Ticket file uploads with client visibility controls.
- Proposal/quote module with itemized costs, payment schedule, signer, PDF generation, and WhatsApp sharing.
- Branded transactional email notifications for client updates and admin new-ticket alerts.

## Local Setup

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
composer run dev
```

The development command starts PHP's built-in server with larger upload limits and also starts Vite/log helpers. If you run PHP manually, use the `public` directory as the document root.

## Verification

```bash
composer validate --strict
composer audit --locked
php -d memory_limit=512M vendor/bin/pint --test
php -d memory_limit=512M vendor/bin/phpunit
npm run build
npm audit --omit=dev
```

## Production Deployment Summary

```bash
ssh -p 65002 u935649387@212.85.28.79
cd /home/u935649387/apps/igna-studio
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
rm -rf /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
cp -R /home/u935649387/apps/igna-studio/public/build /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
```

Never commit or upload `.env`, SSH keys, raw private uploads, or local cache files.
