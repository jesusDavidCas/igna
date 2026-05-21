# IGNA Studio Technical Documentation

Last reviewed: 2026-05-21

## Project Overview

IGNA Studio Platform is a lightweight Laravel application for managing IGNA Studio's public service intake and internal delivery workflow. It combines a bilingual public website, configurable service catalog, request-to-ticket flow, project tracking, admin backoffice, client portal, blog, team credentials, file delivery, proposal generation, and transactional email notifications.

The system is intentionally simple: Laravel owns the business logic, Blade renders server-side views, Tailwind CSS provides styling, MySQL stores operational data, and private Laravel storage currently stores uploaded files while Google Drive remains a future-ready backend.

## Purpose Of The System

The platform helps IGNA Studio receive new project requests, classify them by service, generate a trackable ticket, manage progress through service-specific stages, share selected files with clients, publish blog content, present professional profiles/credentials, and prepare client-facing proposals.

The product rule is configurability without overengineering: new services, stages, and deliverables should be manageable from the admin panel without requiring code changes.

## Main Functionalities

- Public homepage with bilingual Spanish/English content.
- Public request form that creates tickets with unique codes.
- Ticket tracking by ticket code and email.
- Admin dashboard for tickets, services, stages, deliverables, files, blog posts, users, settings, team profiles, credentials, and proposals.
- Client portal called "My Services" for assigned tickets and visible files.
- Service workflow management through `services`, `service_stages`, `service_deliverables`, and `ticket_deliverables`.
- Protected credential viewing with watermarked derivatives/previews.
- Proposal/quote module with dynamic item costs, payment schedule, signer, signature support, PDF generation, and WhatsApp sharing.
- Transactional email notifications for client project updates and internal new-ticket alerts.
- Blog publishing with sanitized HTML and optional header images.
- Google Drive-ready storage service, currently disabled unless configured.

## File And Folder Structure

```text
app/
  Enums/                 Fixed role/status enums.
  Http/
    Controllers/         Public, admin, auth, client, and download controllers.
    Middleware/          Locale and role guards.
    Requests/            Form validation by feature/context.
  Mail/                  Branded customer/admin emails.
  Models/                Eloquent models for business tables.
  Services/              Ticket lifecycle, files/storage, notifications, credentials.
  Support/               Focused helpers for branding, sanitizing, codes, proposals.
config/                  Laravel and IGNA-specific configuration.
database/
  migrations/            Database schema history.
  seeders/               Default settings, services, team, and demo data.
docs/                    Deployment, import, architecture, and maintenance docs.
lang/
  en/, es/               Bilingual UI, service, stage, demo, and validation copy.
public/                  Laravel public entrypoint and built assets only.
resources/
  css/, js/              Tailwind source and small browser JS.
  views/                 Blade templates for public, admin, client, emails, PDFs.
routes/                  Web route map.
tests/                   Feature coverage for public/admin/auth workflows.
```

## Architecture Overview

```text
Browser
  -> Laravel route
  -> Controller
  -> Form Request validation
  -> Service layer for business flow when needed
  -> Eloquent models / MySQL
  -> Blade view or redirect
  -> Optional Mail / File / PDF response
```

Production is deployed with the Laravel application outside the public web root:

```text
/home/u935649387/apps/igna-studio
```

The public web folder is:

```text
/home/u935649387/domains/ignastudio.com/public_html/igna-app
```

Important deployment note: Laravel's `public_path()` points to the app root `public` folder, so built assets must be present in both the Laravel app public folder and the Hostinger exposed folder when using the current split-root deployment.

## Main Modules And Components

### Public Website

Key files:

- `routes/web.php`
- `app/Http/Controllers/Public/LandingController.php`
- `resources/views/public/home.blade.php`
- `resources/views/layouts/public.blade.php`
- `lang/en/site.php`
- `lang/es/site.php`

Responsibilities:

- Render bilingual homepage and service positioning.
- Show team profile links, blog cards, tracking links, and request form.
- Switch locale using session-based language selection.

### Request And Ticket Lifecycle

Key files:

- `app/Http/Controllers/Public/ServiceRequestController.php`
- `app/Services/Tickets/TicketLifecycleService.php`
- `app/Support/Tickets/TicketCodeGenerator.php`
- `app/Models/Ticket.php`
- `app/Models/TicketStageEvent.php`

Flow:

1. Visitor submits public request.
2. `StoreServiceRequestRequest` validates data.
3. `TicketLifecycleService::createFromPublicRequest()` creates the ticket.
4. Ticket code is generated as `IGNA-YYYY-00001`.
5. Active service stages and deliverables are copied to ticket records.
6. Client receives request-confirmation email.
7. Admin/support recipients receive internal new-ticket email.

### Services, Stages, And Deliverables

Key files:

- `app/Http/Controllers/Admin/ServiceController.php`
- `app/Http/Controllers/Admin/ServiceStageController.php`
- `app/Models/Service.php`
- `app/Models/ServiceStage.php`
- `app/Models/ServiceDeliverable.php`
- `app/Models/TicketDeliverable.php`

Purpose:

- Keep business services configurable.
- Support digital and engineering business lines.
- Allow service-specific workflow stages and deliverable slots.
- Let tickets inherit deliverables from the selected service.

### Admin Tickets And Files

Key files:

- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Requests/Admin/TicketFileUploadRequest.php`
- `app/Services/Files/GoogleDriveFileManager.php`
- `app/Http/Controllers/TicketFileDownloadController.php`
- `resources/views/admin/tickets/show.blade.php`
- `resources/views/partials/ticket-file-card.blade.php`
- `resources/views/partials/ticket-timeline.blade.php`

Responsibilities:

- Move tickets forward/back one stage.
- Mark stages completed or reopened.
- Upload files to ticket deliverables.
- Toggle client visibility.
- Send file-available notifications only when a file becomes client-visible.
- Protect downloads through role checks, signed URLs, and visibility checks.

### Client Portal And Public Tracking

Key files:

- `app/Http/Controllers/Client/PortalController.php`
- `app/Http/Controllers/Client/TicketController.php`
- `app/Http/Controllers/Public/TicketTrackingController.php`
- `resources/views/client/dashboard.blade.php`
- `resources/views/client/tickets/show.blade.php`
- `resources/views/public/tracking.blade.php`

Access model:

- Authenticated clients see only tickets assigned to their user.
- Public tracking requires ticket code and email.
- Public file downloads use temporary signed URLs plus an email hash.

### Team Profiles And Credentials

Key files:

- `app/Http/Controllers/Admin/TeamMemberController.php`
- `app/Http/Controllers/Admin/TeamCredentialController.php`
- `app/Http/Controllers/Public/TeamCredentialController.php`
- `app/Services/Credentials/CredentialPreviewRenderer.php`
- `app/Models/TeamMember.php`
- `app/Models/TeamCredential.php`
- `app/Models/TeamCredentialView.php`

Security model:

- Original credential files are stored privately.
- Public access uses signed, throttled routes.
- PDFs/images are served as watermarked preview/derivative output.
- Browser download and screenshots cannot be fully prevented; watermarking, signed access, private storage, and view logging are the practical controls.

### Blog

Key files:

- `app/Http/Controllers/Admin/BlogPostController.php`
- `app/Http/Controllers/Public/BlogController.php`
- `app/Support/Html/HtmlSanitizer.php`
- `app/Models/BlogPost.php`
- `resources/views/admin/blog/*`
- `resources/views/public/blog/*`

Security:

- Admin HTML is sanitized before storage/rendering.
- Public rendering uses sanitized `body_html`.
- Blog posts support soft deletes.

### Proposals / Quotes

Key files:

- `app/Http/Controllers/Admin/ProposalController.php`
- `app/Http/Controllers/Public/ProposalController.php`
- `app/Http/Requests/Admin/ProposalRequest.php`
- `app/Models/Proposal.php`
- `app/Models/ProposalItem.php`
- `app/Support/Proposals/ProposalNumberGenerator.php`
- `resources/views/admin/proposals/*`
- `resources/views/public/proposals/show.blade.php`

Capabilities:

- Professional proposal numbering.
- Dynamic cost items with categories.
- Payment schedule validation totaling 100%.
- Timeline in months/weeks.
- Proposal validity days/expiration date.
- Signer/signature selection.
- A4 landscape PDF export with Dompdf.
- Signed public proposal view.
- WhatsApp message/link generation.

### Email Notifications

Key files:

- `app/Mail/ProjectUpdateMail.php`
- `app/Mail/AdminNewTicketMail.php`
- `app/Services/Notifications/ProjectNotificationService.php`
- `resources/views/emails/project-update.blade.php`
- `resources/views/emails/admin-new-ticket.blade.php`

Triggers:

- Client: request received.
- Client: stage changed.
- Client: stage completed.
- Client: stage reopened/corrected.
- Client: file available.
- Admin/support: new public ticket created.

Production SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=support@ignastudio.com
MAIL_PASSWORD='mailbox-password'
MAIL_FROM_ADDRESS=support@ignastudio.com
MAIL_FROM_NAME="IGNA Studio"
MAIL_REPLY_TO_ADDRESS=support@ignastudio.com
MAIL_REPLY_TO_NAME="IGNA Studio"
```

## Dependencies And Their Role

### PHP

- `laravel/framework`: application framework.
- `dompdf/dompdf`: proposal PDF generation.
- `google/apiclient`: future Google Drive API integration.
- `setasign/fpdf`: watermarked credential PDF generation.
- `setasign/fpdi`: importing credential PDFs before watermarking.
- `laravel/tinker`: local/server diagnostics.

### PHP Dev

- `fakerphp/faker`: test/demo data.
- `laravel/pail`: local log tailing.
- `laravel/pint`: formatting.
- `mockery/mockery`, `phpunit/phpunit`, `nunomaduro/collision`: testing.

### Frontend

- `vite`: asset bundling.
- `laravel-vite-plugin`: Laravel/Vite integration.
- `tailwindcss` and `@tailwindcss/vite`: utility CSS build.
- `concurrently`: local dev command orchestration.

## Configuration Requirements

Production `.env` must include:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://ignastudio.com`
- MySQL credentials.
- `QUEUE_CONNECTION=sync` unless a queue worker is intentionally introduced.
- SMTP credentials for `support@ignastudio.com`.
- `GOOGLE_DRIVE_ENABLED=false` until the Drive service account is fully configured.
- `SUPER_ADMIN_PASSWORD` before production seeding.

Deployment must ensure:

- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan storage:link`
- `npm run build` locally or on server.
- Built assets synced to the exposed Hostinger public folder and Laravel app public folder.
- `php artisan optimize:clear`, then `config:cache`, `route:cache`, and `view:cache`.

## Testing And Quality Gates

Current verified commands:

```bash
composer validate --strict
composer audit --locked
php -d memory_limit=512M vendor/bin/pint --test
php -d memory_limit=512M vendor/bin/phpunit
npm run build
npm audit --omit=dev
```

Browser-smoke-tested locally:

- Homepage.
- Tracking page.
- Blog index.
- Login.
- Admin dashboard.
- Admin tickets.
- Admin proposals.

## Storage And Cleanup Notes

Ignored/generated files that should not be uploaded as application source:

- `.DS_Store`
- `.phpunit.result.cache`
- `public/hot`
- `node_modules/`
- `vendor/`
- `storage/logs/*.log`
- local SSH keys such as `igna` and `igna.pub`
- `database/database.sqlite`
- `database/exports/*.sql`
- `database/exports/*.tar.gz`

`public/hot` is especially risky because it makes Laravel try to load assets from a Vite development server. Do not copy it to Hostinger.

## Risks And Technical Debt

- `Admin\ProposalController` and proposal Blade views are large and should eventually be split into focused services/view partials.
- Google Drive integration is present but disabled; enabling it requires service-account configuration, folder ownership decisions, and operational testing.
- Credential protection is a deterrence strategy, not absolute DRM. Browser screenshots and screen recording cannot be prevented.
- File scanning/antivirus is not implemented.
- Email delivery is synchronous. This matches the no-cron/no-worker rule, but slow SMTP could delay user actions.
- Production backups are manual unless Hostinger-native backups are configured.
- The deployment has a split public folder, so build assets must be kept synchronized carefully.

## Future Maintenance Recommendations

- Run the quality gate commands before every deployment.
- Keep Composer and npm audits part of release preparation.
- Add a small admin "Send test email" button for SMTP verification.
- Add an admin-visible notification log if email delivery becomes business-critical.
- Add a documented monthly backup process for MySQL and private storage.
- Before enabling Google Drive, test upload, download, permissions, folder mapping, and fallback behavior in staging.
- Extract proposal form/table JavaScript into a small dedicated JS module if it grows further.
- Keep demo exports out of production and remove them from local workspace when no longer needed.
