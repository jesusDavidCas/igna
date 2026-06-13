# IGNA Studio Platform Security Audit Report

This document records the security parameters, verification details, and mitigation strategies implemented in the IGNA Studio Platform codebase.

---

## 1. Authentication and Session Security

* **Password Hashing:** Enabled automatically on the `User` model via the `'password' => 'hashed'` Eloquent attribute casting, utilizing bcrypt with default rounds (`BCRYPT_ROUNDS=12` in `.env.example`).
* **Session Lifecycle:** 
  * Active session invalidation and token regeneration (`$request->session()->regenerate()`) are performed on login (`AuthenticatedSessionController@store`).
  * Full session invalidation (`session()->invalidate()`) and token clearing (`session()->regenerateToken()`) are executed on logout (`AuthenticatedSessionController@destroy`) to mitigate session hijacking.
* **Rate Limiting (Throttling):**
  * Login attempts: Throttled to 5 requests per minute (`throttle:5,1` in `routes/web.php`).
  * Password Reset request: Throttled to 3 requests per minute (`throttle:3,1`).
  * Password Reset store: Throttled to 5 requests per minute (`throttle:5,1`).
* **CSRF Protection:** Default Laravel CSRF protection is active globally. No exceptions or exclusions are configured in `bootstrap/app.php`.

---

## 2. Authorization and Route Access Control

* **Role Middleware:** Handled via the custom `App\Http\Middleware\EnsureUserRole` middleware, mapped to the `role` alias. It resolves and validates active users against allowed `UserRole` enums.
* **Routing Segregation:**
  * **Admin Panel:** Locked under the `admin` prefix using `middleware(['auth', 'role:super_admin,admin'])`.
  * **Super Admin Controls:** Nested under the `admin` namespace using `middleware('role:super_admin')` to isolate password changes (`AdminUserController@updatePassword`) and brand settings (`AdminSettingController`).
  * **Client Portal:** Isolated under the `portal` prefix with `middleware(['auth', 'role:client'])`.
* **Access Boundaries & IDOR Prevention:**
  * **Client Dashboard:** Queries tickets via the authenticated relationship (`$user->tickets()`) to prevent direct object reference.
  * **File Downloads:** Handled by `TicketFileDownloadController`, which enforces ownership checks (`$ticket->client_user_id === $request->user()->id`) and visibility controls (`$file->is_client_visible`).

---

## 3. Input Validation and Mass Assignment

* **Form Request Validation:** All inputs modifying the database (POST, PUT, PATCH, DELETE) are validated using dedicated FormRequest classes (e.g., `ProposalRequest`, `BlogPostRequest`, `TeamCredentialRequest`, `StoreServiceRequestRequest`).
* **Mass Assignment:** Eloquent models explicitly define only safe fields in their `$fillable` arrays, preventing injection of admin-only fields like `role` or `is_active` through user requests.

---

## 4. File Upload Security

* **Validation Controls:** Size limits and MIME types are strictly validated:
  * Public requests: Maximum 10MB limit with allowed file extension whitelist (`mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,zip,dwg,dxf`).
  * Team credentials: Maximum 15MB limit with allowed preview extension whitelist (`mimes:pdf,jpg,jpeg,png,webp`).
* **Secure Storage Fallback:** When Google Drive storage is disabled, files are stored on the `local` private disk (mapping to `storage/app/private`). This directory is situated outside the web server's public root, preventing direct download via raw URL paths.
* **Name Sanitization:** Files are renamed using unique UUIDs (`Str::uuid()`) to prevent filename collisions, path traversal attacks, and leakage of client-side local paths.

---

## 5. SQL and Query Safety

* **Query Binding:** Parameterized bindings are automatically used by Eloquent and Laravel's query builder. No raw SQL strings containing concatenated parameters are utilized in the codebase.
* **Database Transactions:** Modifying operations affecting multiple tables (such as proposal creation and ticket stage updates) are wrapped in `DB::transaction()` blocks to preserve integrity and avoid partial writes.

---

## 6. XSS (Cross-Site Scripting) Prevention

* **Default Escaping:** Blade's default `{{ $value }}` escaping wraps all output in `htmlspecialchars()`.
* **Blog Content HTML Sanitization:** Rich blog post text is cleaned prior to rendering via the `App\Support\Html\HtmlSanitizer` utility. This class filters tags against a strict whitelist (`ALLOWED_TAGS`) and removes style properties, script tags, event handlers (`on*`), and JavaScript links (`href="javascript:..."`).

---

## 7. Sensitive Data Exposure

* **Production Environments:** Production configurations must set `APP_DEBUG=false` to suppress stack traces and error details.
* **Credential Isolation:** The `.env` file and private key files (`/igna`, `/igna.pub`) are ignored in Git via `.gitignore`. Google Drive service account JSON paths must refer to local private paths on Hostinger.

---

## 8. Public Token and Signature Verification

* **Public Proposals:** Access links utilize cryptographically secure 40-character tokens (`Str::random(40)`) to ensure URLs are random and non-guessable.
* **HMAC Verification:** Signed routing (`URL::signedRoute` / `URL::temporarySignedRoute`) verifies integrity for credential views and public ticket tracking downloads.
