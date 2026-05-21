# Security And Storage Notes

Last updated: 2026-05-21

This document explains the practical security model of the IGNA Studio platform.

## 1. Security Philosophy

The platform is lightweight, so the security model favors clear Laravel controls rather than complex enterprise infrastructure.

Current controls:

- Authentication for admin and client areas.
- Fixed roles: `super_admin`, `admin`, `client`.
- Role middleware on protected routes.
- Signed URLs for public-sensitive resources.
- Throttling on public forms and signed credential/proposal views.
- Private file storage by default.
- Database-driven file visibility.
- Watermarked credential previews.
- Sanitized blog HTML.
- CSRF protection on forms.
- Confirmation prompts for sensitive admin actions.

## 2. Authentication And Roles

Public users can access:

- Homepage
- Blog
- Team profiles
- Public request form
- Tracking form
- Signed proposal links
- Signed credential links

Authenticated admin users can access:

- `/admin`

Authenticated clients can access:

- `/portal`

Role enforcement:

```text
app/Http/Middleware/EnsureUserRole.php
```

## 3. Public Request Protection

The public request endpoint is throttled:

```text
POST /request -> throttle:10,1
```

This reduces spam but does not replace a full anti-spam system.

Possible future improvement:

- Add CAPTCHA or honeypot only if spam becomes a real problem.

## 4. File Storage Model

Current production default:

```env
GOOGLE_DRIVE_ENABLED=false
```

Files are stored in Laravel private storage:

```text
storage/app/private
```

File metadata is stored in:

```text
ticket_files
```

The public web server should never expose `storage/app/private` directly.

## 5. File Visibility Model

Ticket files have visibility controls.

Clients can only access files when:

- They own the ticket through the client portal, or
- They have a valid signed tracking link, and
- The file is marked client-visible.

Admins can change visibility from the ticket detail area.

Important:

Changing file visibility is a client-facing action and should always use a confirmation modal or prompt.

## 6. Google Drive Storage

Google Drive support exists in:

```text
app/Services/Files/GoogleDriveFileManager.php
```

Required configuration:

```env
GOOGLE_DRIVE_ENABLED=true
GOOGLE_DRIVE_ROOT_FOLDER_ID=...
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON=...
```

Current status:

- Ready to plug in.
- Disabled by default.
- Local private storage is the active fallback.

Security note:

The service account JSON file must never be committed to Git.

## 7. Credential Protection

Credential files are sensitive because they may include diplomas, certificates, specializations, or professional documents.

Current controls:

- Original credential files are stored privately.
- Public access uses signed routes.
- Routes are throttled.
- Views are logged in `team_credential_views`.
- Public previews are watermarked.
- PDF/image previews include repeated IGNA Studio and warning text.

Important limitation:

No browser-based system can fully prevent screenshots, screen recording, camera photos, or manual copying. The platform can discourage misuse and avoid exposing originals, but it cannot provide absolute prevention.

Practical protection used here:

- Do not expose raw private file paths.
- Serve files through Laravel routes.
- Watermark previews.
- Use signed temporary URLs.
- Log views.
- Keep originals outside public storage.

## 8. Blog HTML Safety

Blog content supports HTML, but it must be sanitized.

Relevant file:

```text
app/Support/Html/HtmlSanitizer.php
```

Public rendering should only output sanitized HTML.

## 9. Proposal Public Links

Proposal public views use signed routes:

```text
GET /proposals/{proposal}/view
```

These links are used for WhatsApp sharing and client review.

Important:

Signed links are safer than open predictable URLs, but anyone with the valid link may be able to view it while the signature is valid. Be careful when forwarding links.

## 10. Email Security

Sender:

```text
support@ignastudio.com
```

Production should use:

- SMTP SSL/TLS.
- Strong mailbox password.
- SPF/DKIM/DMARC configured in the domain DNS if available through Hostinger.

Do not commit mailbox credentials.

## 11. Upload Limits

Laravel validates upload size, but PHP limits are checked before Laravel receives the request.

Current development server command raises:

```text
upload_max_filesize=25M
post_max_size=25M
```

Admin validation currently supports files up to 20 MB.

If users see HTTP 413, increase PHP/server limits.

## 12. Secrets And Sensitive Files

Never commit:

- `.env`
- SSH private keys
- Google service account JSON
- Raw backups
- Private uploaded files
- Production database dumps

Review before commit:

```bash
git status
git diff --cached --stat
```

## 13. Recommended Future Security Improvements

- Add automated backup rotation.
- Add malware scanning for uploaded files if the platform receives unknown external files frequently.
- Add a dedicated audit log table for admin changes.
- Add two-factor authentication for admin users if the project grows.
- Add stricter signed-link expiry policies for proposals and credentials.
- Move large/private files fully to Google Drive or another external object storage provider.
