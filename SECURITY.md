# Security Notes

## Current Baseline

- Admin and client routes require authentication and role middleware.
- Public request and tracking submission routes are rate-limited.
- Client downloads are restricted to assigned tickets and client-visible files.
- Public tracking downloads use signed URLs and an email hash check.
- Admin uploads validate file type and size before storing metadata.
- Blog HTML is sanitized before public rendering.
- `.env`, service account JSON files, Composer auth files, build artifacts, logs, and vendor dependencies are ignored by Git.

## Production Requirements

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Generate a new `APP_KEY` on the server with `php artisan key:generate`.
- Configure `SUPER_ADMIN_PASSWORD` before seeding production.
- Use a strong database password and never commit `.env`.
- Keep `GOOGLE_DRIVE_ENABLED=false` until the Drive integration is intentionally configured.
- Change the seeded admin password immediately after first login.
- Run `composer audit` and `npm audit --omit=dev` before each production release.

## Deferred Hardening

- Add email verification and password reset flows before inviting external clients.
- Add antivirus scanning for uploaded files if uploads become public-facing at scale.
- Add server-level upload limits matching the Laravel validation limit.
- Add backup and restore procedures for MySQL and uploaded files.
