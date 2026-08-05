# Phase 5B.3 Team Photo Delivery Repair

Date: 2026-08-05

Branch: `fix/team-photo-delivery`

Starting release checkpoint: `c56832d83d36cb07d35f1090b428e95f4121af56`

Implementation commit: `39e0400b9490cc21930ab68c97b3626f36720c10`

## Root Cause

Team member photos were stored on the Laravel `public` disk and rendered with `Storage::disk('public')->url($path)`. That generated `/storage/team/photos/...` URLs and made public delivery depend on a `public/storage` symbolic link or equivalent web-server bridge.

The local bridge reproduced the production risk: `public/storage` was missing or broken and a disposable Team Member photo returned `HTTP 403` at `/storage/team/photos/phase-5b3-http-proof.png`, while the homepage returned `HTTP 200`.

## Corrected Architecture

Team photos are still stored under `storage/app/public/team/photos`, but public rendering now uses an app-served route:

`GET /team/{teamMember:slug}/photo`

The route is named `team.photo`, throttled, served by `App\Http\Controllers\Public\TeamPhotoController`, and backed by `App\Services\Team\TeamPhotoManager`.

The `TeamMember::photoUrl()` model helper is now the single resolver for public Team photo URLs. It returns the Laravel route with a deterministic `v` cache token derived from the member id, stored path, and `updated_at` timestamp. Views no longer call `Storage::url()` or render `/storage/team/photos/...`.

## Security Controls

- Public route binds only by Team Member slug.
- Inactive Team Members return 404.
- The route accepts no user-supplied storage path.
- Stored paths are accepted only when they are under `team/photos/`.
- Paths with traversal, control characters, leading slashes, backslashes, or other private directories fail closed.
- Missing, unreadable, or non-image files return a generated initials PNG instead of a broken image or private path.
- Credential, proposal, ticket, and other private files cannot be served through the Team photo route.
- Responses include `Cache-Control`, `ETag`, `Content-Length`, and `X-Content-Type-Options: nosniff`.

## Upload Validation and Normalization

Admin Team Member uploads now require:

- JPG, PNG, or WebP extension.
- Actual MIME type of `image/jpeg`, `image/png`, or `image/webp`.
- Laravel image decoding.
- Minimum dimensions of 300x300 px.
- Maximum dimensions of 6000x6000 px.
- Maximum file size of 5 MB.

Accepted uploads are normalized through GD into JPEG files under `team/photos/{uuid}.jpg`. This strips uploaded metadata, standardizes delivery, applies EXIF orientation for JPEG sources when available, and downsizes images whose longest edge exceeds 1600 px.

Replacing a photo deletes only the previous public file when no other Team Member still references that same path.

## Responsive Presentation

A reusable Blade component now renders Team photos for:

- Home page cards.
- Public profile pages.
- Admin Team list avatars.

The card variant keeps the existing 4:5 crop. The public profile variant uses a square responsive frame. The admin avatar variant uses the compact square frame. Images include stable width/height attributes plus `loading="lazy"` and `decoding="async"`.

When `photo_path` is empty, the component renders an accessible initials fallback rather than an image tag.

## Verification

Focused tests:

`php artisan test --filter=TeamPhotoDeliveryTest`

Result: 13 passed, 62 assertions.

Full suite:

`php artisan test`

Result: 191 passed, 1638 assertions.

Additional verification:

- `composer validate`: passed.
- `composer audit --locked`: no security vulnerability advisories found.
- `npm audit --omit=dev`: found 0 vulnerabilities.
- `npm run build`: passed with the known Node `module.register()` deprecation warning.
- `git diff --check`: passed.
- `php artisan route:list --except-vendor`: `team.photo` present and public.
- `php artisan migrate:status`: no new Phase 5B.3 migration required.
- Browser QA: public profile routed photo loaded at natural size 640x800, desktop profile frame rendered 160x160, home card frame rendered 152x190 desktop and 335x419 mobile, no `/storage/team/photos` references were present, and no browser console warnings or errors were recorded.
- Local route header QA: corrected photo endpoint returned `HTTP 200`, `Content-Type: image/png`, `Content-Length`, `Cache-Control`, `ETag`, and `X-Content-Type-Options: nosniff`.

Full `npm audit` still reports development-only advisories in PostCSS, Vite, and concurrently/shell-quote. Production audit remains clean and no dependency changes were made in this phase.

## Deployment Notes

No migration is included.

Production deploy must ensure:

- `storage/app/public/team/photos` exists or can be created by the Laravel filesystem.
- The storage directory remains writable by the PHP runtime.
- PHP GD remains enabled.
- Existing `photo_path` values remain in `team/photos/...`.
- The public bridge must continue routing requests to Laravel for `/team/{slug}/photo`.

The fix intentionally does not require `php artisan storage:link`, a public storage symlink, or exposing `storage/app/public` through the Hostinger public bridge.

## Remaining Risks

- Existing stored source photos are not bulk-normalized until replaced or manually processed.
- If GD is unavailable in a future environment, new upload normalization will fail; production GD capability should remain part of deployment checks.
- A temporary network failure can still prevent a browser image request from loading, but missing/corrupt storage files now produce an app-generated fallback image.
