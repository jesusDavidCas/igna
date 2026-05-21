# IGNA Studio Platform Project Manual

Last updated: 2026-05-21

## 1. Purpose

IGNA Studio Platform exists to help IGNA Studio receive, organize, track, and deliver technical service work through one lightweight system.

The platform supports two business lines:

- Digital and technology services: web platforms, simple customer/request tracking systems, digital project structuring, and technical project management.
- Water infrastructure and civil engineering services: aqueduct, sanitary sewer, stormwater sewer, fire protection, hydrology, drinking water treatment, and wastewater treatment projects.

The core business flow is:

```text
Visitor lands on the website
  -> submits a project request
  -> Laravel creates a ticket
  -> service workflow stages are copied into the ticket timeline
  -> service deliverables are copied into ticket deliverables
  -> client tracks progress by ticket code and email
  -> admin updates stages, uploads files, and sends updates
  -> client sees progress and allowed files
```

## 2. Design Principles

- Keep the public website fast and lightweight.
- Prefer Blade, Tailwind CSS, and minimal JavaScript.
- Keep MySQL as the source of truth for records and metadata.
- Store uploaded files outside the public web root.
- Use Google Drive only when explicitly configured.
- Avoid unnecessary abstractions and package-heavy patterns.
- Make services, stages, and deliverables configurable from admin.
- Keep bilingual Spanish/English support centralized in `lang/`.
- Use confirmation dialogs for sensitive admin actions.
- Preserve traceability instead of silently rewriting history.

## 3. Current Production Shape

```text
Domain:              https://ignastudio.com
Laravel app:         /home/u935649387/apps/igna-studio
Public web folder:   /home/u935649387/domains/ignastudio.com/public_html/igna-app
Database:            Hostinger MySQL
Public build folder: /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
```

The Laravel app is not directly inside the public domain root. The domain uses a small public entrypoint that boots the Laravel app from `/home/u935649387/apps/igna-studio`.

This keeps source code, `.env`, private storage, logs, and framework cache away from public access.

## 4. Technology Stack

- PHP `^8.3`
- Laravel `^13.0`
- MySQL in production
- Blade templates
- Tailwind CSS v4
- Vite
- Dompdf for proposal PDF generation
- FPDI/FPDF and GD/Imagick-related rendering for credential previews
- Google API client for future Google Drive storage integration
- Laravel Mail/Symfony Mailer for transactional email
- PHPUnit for tests
- Laravel Pint for style checks

## 5. Roles And Permissions

There are exactly three business roles.

### `super_admin`

Can access everything an admin can access, plus:

- Manage users.
- Reset user passwords.
- Manage settings and branding.
- Configure global platform values.

### `admin`

Can:

- Manage tickets and stage changes.
- Upload and expose ticket files.
- Manage services, stages, and deliverables.
- Manage blog posts.
- Manage team profiles and credentials.
- Manage proposals.

### `client`

Can:

- Access My Services.
- View only assigned tickets.
- View project timeline.
- Download only client-visible files.

Implementation:

- Enum: `App\Enums\UserRole`
- Middleware: `App\Http\Middleware\EnsureUserRole`
- Column: `users.role`
- Route middleware: `role:super_admin,admin` or `role:client`

## 6. Main Folder Structure

```text
app/
  Enums/                 Fixed business enums.
  Http/
    Controllers/         Public, auth, admin, client, and file download controllers.
    Middleware/          Locale and role middleware.
    Requests/            Validation rules by feature.
  Mail/                  Branded email classes.
  Models/                Eloquent models for platform tables.
  Services/              Ticket lifecycle, file storage, notifications, credentials.
  Support/               Small helpers for codes, branding, HTML, proposals.
config/                  Laravel configuration and service credentials.
database/
  migrations/            Schema history.
  seeders/               Initial users, services, team, settings, demo data.
docs/                    Project documentation.
lang/
  en/, es/               Bilingual labels, copy, emails, and validation.
public/                  Public entrypoint and built assets.
resources/
  css/, js/              Tailwind and minimal JavaScript.
  views/                 Blade views for public, admin, client, emails, PDFs.
routes/                  Web route definitions.
storage/                 Private files, logs, framework cache.
tests/                   Feature tests.
```

## 7. Main Modules

### Public Website

Files:

- `app/Http/Controllers/Public/LandingController.php`
- `resources/views/layouts/public.blade.php`
- `resources/views/public/home.blade.php`
- `lang/en/site.php`
- `lang/es/site.php`

Responsibilities:

- Render the bilingual homepage.
- Show service groups, process, projects, team, blog, tracking, and request form.
- Switch locale through `POST /locale/{locale}`.
- Use a responsive desktop navbar and mobile hamburger menu.

### Public Request Intake

Files:

- `app/Http/Controllers/Public/ServiceRequestController.php`
- `app/Http/Requests/Public/StoreServiceRequestRequest.php`
- `app/Services/Tickets/TicketLifecycleService.php`
- `app/Support/Tickets/TicketCodeGenerator.php`

Flow:

1. User submits the public request form.
2. Request validation runs.
3. Ticket is created with a unique code.
4. Active stages are copied from the selected service.
5. Active deliverables are copied from the selected service.
6. Client receives a confirmation email.
7. Admin/support recipients receive a new-ticket email.

Ticket code format:

```text
IGNA-YYYY-00001
```

### Ticket Tracking

Files:

- `app/Http/Controllers/Public/TicketTrackingController.php`
- `app/Http/Controllers/TicketFileDownloadController.php`
- `resources/views/public/tracking.blade.php`
- `resources/views/partials/ticket-timeline.blade.php`

Public tracking requires:

- Ticket code.
- Request email.

Clients only see:

- Current stage.
- Client-visible stage events.
- Client-visible files.
- Notes/update history marked as visible.

### Admin Backoffice

Files:

- `resources/views/layouts/panel.blade.php`
- `app/Http/Controllers/Admin/*`

Admin modules:

- Dashboard
- Tickets
- Services and stages
- Service deliverables
- Files
- Blog
- Team profiles
- Credentials
- Proposals
- Users
- Settings

### Service Management

Files:

- `app/Http/Controllers/Admin/ServiceController.php`
- `app/Http/Controllers/Admin/ServiceStageController.php`
- `app/Models/Service.php`
- `app/Models/ServiceStage.php`
- `app/Models/ServiceDeliverable.php`

Each service supports:

- Name
- Code
- Business line
- Service type
- Service scope
- Description
- Active/inactive state
- Sort order
- Workflow stages
- Expected deliverables

This is critical because future services should be added without code changes.

### Ticket Lifecycle

File:

- `app/Services/Tickets/TicketLifecycleService.php`

Responsibilities:

- Create ticket records.
- Initialize stage events.
- Initialize deliverables.
- Move a ticket to the next valid stage.
- Complete stages.
- Reopen/correct stages.
- Notify clients and admins.

Important rule:

Selecting a stage does not automatically mark that stage as completed. The system separates:

- Current active stage
- Completed stage
- Reopened/corrected stage

### Ticket Files And Deliverables

Files:

- `app/Http/Controllers/Admin/TicketController.php`
- `app/Http/Requests/Admin/TicketFileUploadRequest.php`
- `app/Services/Files/GoogleDriveFileManager.php`
- `app/Models/TicketFile.php`
- `app/Models/TicketDeliverable.php`

Files are uploaded to a ticket and may optionally belong to a ticket deliverable.

Client access is controlled by:

- Role checks
- Ticket ownership checks
- Signed public tracking links
- `is_client_visible`
- Visibility/status fields

### Client Portal

Files:

- `app/Http/Controllers/Client/PortalController.php`
- `app/Http/Controllers/Client/TicketController.php`
- `resources/views/client/dashboard.blade.php`
- `resources/views/client/tickets/show.blade.php`

Clients access:

```text
/portal
```

The UI should refer to this area as My Services rather than Client Portal in user-facing copy.

### Blog

Files:

- `app/Http/Controllers/Admin/BlogPostController.php`
- `app/Http/Controllers/Public/BlogController.php`
- `app/Models/BlogPost.php`
- `app/Support/Html/HtmlSanitizer.php`
- `resources/views/admin/blog/*`
- `resources/views/public/blog/*`

Blog posts support:

- Title
- Slug
- Summary
- Body HTML
- Draft/published status
- Published date
- SEO keywords/tags
- Optional header image
- Soft delete

Public blog HTML is sanitized before rendering.

### Team Profiles And Credentials

Files:

- `app/Http/Controllers/Admin/TeamMemberController.php`
- `app/Http/Controllers/Admin/TeamCredentialController.php`
- `app/Http/Controllers/Public/TeamCredentialController.php`
- `app/Services/Credentials/CredentialPreviewRenderer.php`
- `app/Models/TeamMember.php`
- `app/Models/TeamCredential.php`
- `app/Models/TeamCredentialView.php`

Credentials support:

- Title
- Institution
- Issue date
- Upload
- Public/private visibility
- View counter
- Protected viewing route
- Watermarked previews

Browser limitation:

No web application can fully prevent screenshots or all forms of manual capture. The platform uses practical controls: private storage, signed URLs, throttling, watermarking, and view logging.

### Proposal / Quote Module

Files:

- `app/Http/Controllers/Admin/ProposalController.php`
- `app/Http/Controllers/Public/ProposalController.php`
- `app/Support/Proposals/ProposalNumberGenerator.php`
- `app/Models/Proposal.php`
- `app/Models/ProposalItem.php`
- `resources/views/admin/proposals/*`
- `resources/views/public/proposals/show.blade.php`

Proposals support:

- Proposal number
- Client
- Manual client information
- Title
- Subject
- Description
- Scope
- Timeline in months/weeks
- Payment schedule
- Cost items
- Tax rate
- Subtotal/total
- Signer
- Signature image
- Public signed view
- PDF generation
- WhatsApp share link

Proposal numbers are generated through `ProposalNumberGenerator` to avoid looking like the business is starting at proposal 0001.

### Email Notifications

Files:

- `app/Services/Notifications/ProjectNotificationService.php`
- `app/Mail/ProjectUpdateMail.php`
- `app/Mail/AdminNewTicketMail.php`
- `resources/views/emails/layout.blade.php`
- `resources/views/emails/project-update.blade.php`
- `resources/views/emails/admin-new-ticket.blade.php`

Emails are sent from:

```text
support@ignastudio.com
```

Current trigger cases:

- Public request created: client confirmation.
- Public request created: admin/support notification.
- Stage changed.
- Stage completed.
- File becomes visible to client.

Email uses the ticket/request preferred language where appropriate.

## 8. Route Overview

Public:

- `GET /`
- `POST /locale/{locale}`
- `POST /request`
- `GET /tracking`
- `POST /tracking`
- `GET /tracking/tickets/{ticket}/files/{file}`
- `GET /blog`
- `GET /blog/{post:slug}`
- `GET /team/{slug}`
- `GET /team/{teamMember:slug}/credentials/{credential}/view`
- `GET /team/{teamMember:slug}/credentials/{credential}/file`
- `GET /team/{teamMember:slug}/credentials/{credential}/pages/{page}`
- `GET /proposals/{proposal}/view`

Auth:

- `GET /login`
- `POST /login`
- `POST /logout`

Admin:

- `GET /admin`
- `/admin/services`
- `/admin/tickets`
- `/admin/blog`
- `/admin/team`
- `/admin/proposals`
- `/admin/users`
- `/admin/settings`

Client:

- `GET /portal`
- `GET /portal/tickets/{ticket}`
- `GET /portal/tickets/{ticket}/files/{file}/download`

## 9. Configuration

Production `.env` must include:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ignastudio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=support@ignastudio.com
MAIL_PASSWORD=...
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=support@ignastudio.com
MAIL_FROM_NAME="IGNA Studio"
MAIL_REPLY_TO_ADDRESS=support@ignastudio.com
MAIL_REPLY_TO_NAME="IGNA Studio"

QUEUE_CONNECTION=sync

GOOGLE_DRIVE_ENABLED=false
```

Use `QUEUE_CONNECTION=sync` unless a real queue worker is configured and supervised.

## 10. Build And Assets

Frontend source:

```text
resources/css/app.css
resources/js/app.js
```

Build command:

```bash
npm run build
```

Production asset issue to remember:

Because the Laravel app path and public domain path are split, after building/pulling code the `public/build` folder must be copied to:

```text
/home/u935649387/domains/ignastudio.com/public_html/igna-app/build
```

Do not leave `public/hot` in production. That file tells Laravel to load Vite dev server assets.

## 11. Testing

Recommended verification before pushing:

```bash
composer validate --strict
composer audit --locked
php -d memory_limit=512M vendor/bin/pint --test
php -d memory_limit=512M vendor/bin/phpunit
npm run build
npm audit --omit=dev
```

Manual checks:

- Homepage desktop and mobile.
- Mobile hamburger navigation.
- Language switch.
- Login.
- Admin tickets list/detail.
- Public request form.
- Tracking by ticket code/email.
- File upload and visibility toggle.
- Blog list/detail.
- Team credential view.
- Proposal detail, PDF generation, and WhatsApp share modal.
- Email notification delivery.

## 12. Deployment Summary

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

## 13. What Not To Commit

Never commit:

- `.env`
- SSH keys
- `storage/app/private/*`
- `storage/logs/*`
- `public/hot`
- Local database files
- Demo exports unless intentionally versioned
- Vendor or node dependency folders

## 14. Known Future Work

- Full Google Drive production configuration.
- Better backup automation for database and private files.
- Optional queue worker if email volume grows.
- Proposal Excel import parser.
- Antivirus/malware scan for uploaded files.
- More granular permissions if the admin team grows.
- Automated browser tests for key admin/client flows.
