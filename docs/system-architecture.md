# IGNA Studio System Architecture

This platform is a Laravel application for `ignesstudio.com`. Laravel owns the business logic, Blade renders the UI, MySQL stores the business data, and private file storage holds uploaded files until Google Drive is enabled in a later phase.

## Production Shape

```text
Browser
  -> https://ignesstudio.com
  -> Web server document root: /path/to/igna/public
  -> Laravel application: /path/to/igna
  -> MySQL database: Hostinger/MySQL server
  -> Private uploads: /path/to/igna/storage/app/private
```

The domain must point to Laravel's `public` directory, not the project root. The project root contains private files such as `.env`, source code, framework cache, and storage directories that must not be publicly exposed.

## Where The Database Lives

In production, the database lives in MySQL on the server or hosting account. It is not stored in GitHub and it is not created by Composer.

Laravel connects to MySQL through `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

The tables are created by migrations:

- `users`: administrators, clients, roles, login credentials, language preference.
- `services`: configurable digital and engineering services.
- `service_stages`: configurable workflow stages for each service.
- `tickets`: project/request records generated from the public form.
- `ticket_stage_events`: timeline and progress history for each ticket.
- `ticket_files`: file metadata, visibility, storage provider, and download references.
- `blog_posts`: admin-managed blog content.
- `settings`: company, branding, and platform configuration.
- Laravel system tables: cache, jobs, sessions, and password reset tokens.

## Where Files Live

For this iteration, Google Drive is disabled:

```env
GOOGLE_DRIVE_ENABLED=false
```

Uploaded files are stored in Laravel private storage:

```text
storage/app/private/stubs/tickets/{ticket_code}/...
```

The database stores the file metadata in `ticket_files`. The binary file is not stored in MySQL. Clients can only download files through Laravel routes that check ownership, signed tracking access, and client visibility.

## Runtime Responsibilities

- `routes/web.php`: defines public, auth, admin, and client routes.
- `app/Http/Controllers`: receives requests and returns views or redirects.
- `app/Http/Requests`: validates form submissions and uploads.
- `app/Models`: represents database tables.
- `app/Services/Tickets`: creates tickets and manages workflow movement.
- `app/Services/Files`: stores uploads and prepares future Google Drive support.
- `resources/views`: Blade templates for public site, admin, and My Services.
- `lang/en` and `lang/es`: bilingual interface copy.
- `database/migrations`: database structure.
- `database/seeders`: initial admin, services, stages, settings, and demo data.

## Main Domain Deployment Rule

`ignesstudio.com` should serve:

```text
/path/to/igna/public
```

It should not serve:

```text
/path/to/igna
```

If the hosting panel only gives you `public_html`, the safe goal is to make `public_html` equivalent to Laravel's `public` directory while keeping the rest of the Laravel project outside the public web root.
