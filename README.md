# IGNA Studio Platform

Lightweight Laravel foundation for IGNA Studio's public website, request intake, service-driven ticketing, admin backoffice, customer portal, blog, and Google Drive-backed file management.

## Architecture Summary

- Framework: Laravel 13
- UI: Blade + Tailwind CSS v4 + minimal JavaScript
- Database target: MySQL
- Local verification in this scaffold: SQLite
- Storage strategy: MySQL as source of truth for file metadata, Google Drive as the long-term binary storage backend
- Roles: `super_admin`, `admin`, `client`
- Design approach: modular, service-configurable, no cron dependency, no package-heavy abstractions

## Core Modules

- Public landing page
- Public service request form
- Ticket tracking page by ticket code + email
- Admin backoffice
- Customer portal
- Lightweight blog
- Service and stage management
- File registration, listing, and downloads with a Google Drive storage adapter

## Database Schema

### `users`
- Managed platform accounts for super admins, admins, and clients
- Fixed role stored directly in the table
- Includes first/last name, email, phone, preferred language, active flag, password

### `services`
- Configurable service catalog
- Includes name, slug, code, business line, service type, service scope, description, deliverables schema, active flag, sort order

### `service_stages`
- Per-service workflow stages
- Includes name, code, description, sort order, active flag, client visibility flag

### `tickets`
- Service requests converted into tracked projects
- Includes public form data, ticket code, current stage pointer, status, optional client user link, Google Drive folder placeholders

### `ticket_stage_events`
- One workflow row per ticket/stage combination
- Tracks pending/current/completed/skipped state, timestamps, client visibility, notes, actor

### `ticket_files`
- File metadata owned by the application database
- Tracks title, original file name, storage provider, storage path, Google Drive identifiers, deliverable type, client visibility, watermark placeholder status

### `blog_posts`
- Admin-managed text-first publishing
- Includes title, slug, summary, body HTML, status, publication date, SEO keywords

### `settings`
- Simple key-value configuration store for company/platform settings

## Role Handling Strategy

Roles are fixed and intentionally not abstracted into a dynamic RBAC system in V1.

- `super_admin`
  - Full admin access
  - Future owner of global configuration, users, and system-level management
- `admin`
  - Access to ticket, service, file, and blog operations
- `client`
  - Access only to own portal routes and client-visible ticket data

Implementation uses:

- `App\Enums\UserRole`
- `users.role` enum column
- `App\Http\Middleware\EnsureUserRole`
- Route middleware declarations such as `role:super_admin,admin`

## Route Map

### Public
- `GET /`
- `GET /team/{slug}`
- `POST /locale/{locale}`
- `POST /request`
- `GET /tracking`
- `POST /tracking`
- `GET /blog`
- `GET /blog/{slug}`
- `GET /login`
- `POST /login`
- `POST /logout`

### Admin
- `GET /admin`
- `resource /admin/services`
- `POST /admin/services/{service}/stages`
- `PUT /admin/services/{service}/stages/{stage}`
- `GET /admin/tickets`
- `GET /admin/tickets/{ticket}`
- `PUT /admin/tickets/{ticket}/client`
- `PUT /admin/tickets/{ticket}/stage`
- `POST /admin/tickets/{ticket}/files`
- `PUT /admin/tickets/{ticket}/files/{file}/visibility`
- `resource /admin/blog`
- `resource /admin/users` for `super_admin`
- `GET /admin/settings` and `PUT /admin/settings` for `super_admin`

### Client
- `GET /portal`
- `GET /portal/tickets/{ticket}`

## First File Tree

```text
app/
  Enums/
  Http/
    Controllers/
      Admin/
      Auth/
      Client/
      Public/
    Middleware/
    Requests/
      Admin/
      Auth/
      Public/
  Models/
  Services/
    Files/
    Tickets/
  Support/
    Tickets/
config/
  services.php
database/
  factories/
  migrations/
  seeders/
lang/
  en/
  es/
resources/
  css/
  js/
  views/
    admin/
    auth/
    client/
    layouts/
    partials/
    public/
routes/
  web.php
tests/
  Feature/
```

## Folder Structure Explanation

### `app/Enums`
Small fixed enums for roles and content/status states. This keeps business rules explicit and avoids magic strings.

### `app/Http/Controllers/Public`
Landing page, request submission, blog, tracking, and team-profile delivery for the public website.

### `app/Http/Controllers/Admin`
Backoffice controllers for differentiated service management, ticket operations, file registration, users, settings, and blog management.

### `app/Http/Controllers/Client`
Portal controllers limited to the authenticated client's own tickets and visible artifacts.

### `app/Http/Requests`
Request validation separated by context so public, admin, and auth rules remain easy to maintain.

### `app/Services/Tickets`
Central workflow logic for ticket creation and stage progression. This keeps controllers thin and concentrates business flow in one place.

### `app/Services/Files`
Storage integration boundary. It uses private local storage when Google Drive is disabled, and switches to Google Drive when the service account configuration is present.

### `app/Support/Tickets`
Small utility for ticket code generation.

### `database/migrations`
The schema foundation for configurable services, workflow stages, tickets, files, blog posts, and settings.

### `database/seeders`
Production-oriented defaults for the initial super admin, IGNA service catalog, example workflows, and base settings.

### `resources/views/public`
Blade templates for the public landing, tracking, blog, and profile pages.

### `resources/views/admin`
Blade templates for backoffice dashboards and operational CRUD scaffolding.

### `resources/views/client`
Blade templates for client-facing ticket review and document visibility.

### `lang/en` and `lang/es`
Minimal bilingual content support for the public-facing experience.

## Local Setup

1. Copy environment variables.

```bash
cp .env.example .env
```

2. Configure MySQL credentials in `.env`.

3. Install PHP dependencies.

```bash
composer install
```

4. Install frontend dependencies.

```bash
npm install
```

5. Generate the app key if needed.

```bash
php artisan key:generate
```

6. Run migrations and seeders.

```bash
php artisan migrate --seed
```

7. Build assets.

```bash
npm run build
```

8. Start the local app.

```bash
composer run dev
```

The local dev command starts PHP's built-in server with the `public` directory as the document root, a project-local router at `public/dev-server.php`, plus `upload_max_filesize=25M` and `post_max_size=25M`. Laravel validation accepts admin files up to 20 MB. Avoid starting this project with plain `php artisan serve` unless your PHP configuration also raises those limits, because Herd's default CLI server limit may be only 2 MB.

## Default Seeded Access

Local development falls back to these credentials when no `SUPER_ADMIN_PASSWORD` is configured:

- Email: `admin@ignastudio.com`
- Password: `Igna12345!`

For production, set `SUPER_ADMIN_EMAIL` and `SUPER_ADMIN_PASSWORD` in `.env` before running `php artisan db:seed --force`. Production seeding will fail intentionally if `SUPER_ADMIN_PASSWORD` is missing.

## Deployment

Hostinger deployment notes and pull commands are documented in [`docs/hostinger-deployment.md`](docs/hostinger-deployment.md). A repeatable update script is available at [`scripts/deploy-hostinger.sh`](scripts/deploy-hostinger.sh).

## Deferred Items and Explicit TODO Markers

The scaffold includes clear TODO markers for:

- Google Drive production credential rotation and operational monitoring
- Email notifications on request creation and stage updates
- Watermarking of partial deliverables before external sharing

## Google Drive Storage Model

The user experience should always feel like files belong to this platform. Admins upload from the ticket screen, clients download from tracking or My Services, and nobody needs to know where the binary file actually lives.

The database remains the source of truth. `ticket_files` stores the title, original filename, visibility, deliverable type, storage provider, storage path, Google Drive file id, and Google Drive URL.

Google Drive is disabled by default for this iteration. Uploaded files are stored in Laravel's private local disk while `GOOGLE_DRIVE_ENABLED=false`. The future-ready Drive adapter remains in `App\Services\Files\GoogleDriveFileManager`; when enabled later, it can create a folder for the ticket inside the configured Drive root folder, upload the file there, and save the Drive identifiers in MySQL.

Download routes stay inside the platform. The controller checks ticket ownership or signed tracking access, then streams the local file or retrieves the Drive file through the server so the client experience remains inside IGNA Studio.

### Google Drive Setup

1. Create or choose a Google Cloud project, enable the Google Drive API, and create a service account.
2. Download the service account JSON key and place it at `storage/app/private/google-drive-service-account.json`. Do not commit this file.
3. Create the root folder in Google Drive where ticket folders should live.
4. Share that root folder with the service account email as an editor.
5. Copy the root folder id from the Google Drive URL and set these values in `.env`:

```env
GOOGLE_DRIVE_ENABLED=true
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON=storage/app/private/google-drive-service-account.json
GOOGLE_DRIVE_ROOT_FOLDER_ID=your-google-drive-folder-id
```

6. Run `php artisan config:clear`, then upload a file from an admin ticket page and download it from My Services or the public tracking page.

## Assumptions

- Public requests do not auto-create client accounts in V1.
- My Services access is for managed `client` users linked to tickets by the admin.
- MySQL is the intended application database, but SQLite was used locally to validate migrations and tests inside this scaffold.
- No cron-based behavior is required for the current iteration.
