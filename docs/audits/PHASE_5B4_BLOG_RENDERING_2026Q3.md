# Phase 5B.4 Blog Rendering Audit

Date: 2026-08-15
Branch: `fix/blog-publishing-rendering`
Base HEAD: `3b643313608fab959c25727f20b4e92baeee608f`

## Original Symptoms

- Public Blog article body rendered sanitized HTML, but editorial spacing depended on unavailable/default typography styling.
- Headings, paragraphs, and lists visually flattened when Tailwind reset styles removed browser defaults.
- Blog header images rendered as broken images in admin preview and public article surfaces.

## Blog HTML Pipeline

- Article HTML field: `blog_posts.body_html`.
- Admin write path: `App\Http\Controllers\Admin\BlogPostController::payload()`.
- Sanitizer: `App\Support\Html\HtmlSanitizer`.
- Public render path: `App\Models\BlogPost::localizedBodyHtml()`, then raw Blade output in `resources/views/public/blog/show.blade.php`.
- Sanitization is preserved on write and again before public render.

## Header Image Pipeline

- Header path field: `blog_posts.header_image_path`.
- Disk: Laravel `public` disk.
- Storage directory: `storage/app/public/blog/headers`.
- Previous URL: `Storage::disk('public')->url($path)`, producing `/storage/blog/headers/...`.
- Local proof URL: `http://127.0.0.1:8000/storage/blog/headers/phase-5b4-proof.png`.
- Local proof result: `HTTP/1.1 403 Forbidden` while the file existed in `storage/app/public/blog/headers/...`.

## Root Cause

Blog image rendering depended on the public storage bridge at `/storage/...`. In this local checkout, `public/storage` was not a reliable delivery path, matching the previously corrected Team-photo class of failure.

## Corrected Architecture

- Added public route: `GET /blog/{post:slug}/header-image`.
- Route name: `blog.header-image`.
- Added `App\Http\Controllers\Public\BlogHeaderImageController`.
- Added `App\Services\Blog\BlogHeaderImageManager`.
- `BlogPost::headerImageUrl()` now returns the route-backed URL with deterministic versioning.
- No filesystem path is accepted from the request.
- Only `blog/headers/` paths are approved.
- Traversal, absolute paths, backslashes, control characters, unrelated directories, invalid images, missing files, and private credential paths fail closed.
- Valid JPEG, PNG, and WebP files return the detected MIME type, `Content-Length`, `ETag`, public cache headers, and `X-Content-Type-Options: nosniff`.
- Missing/no valid header image returns no rendered article image; the route returns 404 when directly requested.

## Typography Solution

- Replaced reliance on generic `prose` classes with dedicated `.blog-article-content` CSS.
- Added editorial rhythm for paragraphs, H2, H3, lists, links, blockquotes, emphasis, code, and responsive images.
- Article body remains semantic HTML; content text was not changed.
- Public article layout now includes a constrained title/summary column, 16:9 hero image, and a 680-820px-class reading column.

## Admin Preview

- Admin Blog edit preview now uses the same `BlogPost::headerImageUrl()` resolver.
- The preview shows a clean thumbnail without exposing storage paths.
- Replacement upload control remains intact.

## Responsive QA

Evidence saved under:

- `output/qa/phase-5b4-blog-rendering/browser-qa.json`
- `output/ui-review/phase-5b4-blog-rendering/desktop.png`
- `output/ui-review/phase-5b4-blog-rendering/tablet.png`
- `output/ui-review/phase-5b4-blog-rendering/mobile.png`

Checked desktop, tablet, and mobile. Browser metrics confirmed:

- no failed images;
- no `/storage/blog` markup;
- routed header image in markup;
- no horizontal overflow;
- article wrapper present;
- H2/H3 semantic markup preserved;
- list marker style restored to `disc`;
- `li` display restored to `list-item`;
- console warnings/errors: none.

## Test Results

- `php artisan test --filter=Blog`: passed, 13 tests, 110 assertions.
- `php artisan test`: passed, 201 tests, 1725 assertions.
- `npm run build`: passed.
- `npm audit --omit=dev`: passed, 0 vulnerabilities.
- `composer validate`: passed.
- `composer check-platform-reqs`: passed.
- `git diff --check`: passed.

## Security Maintenance Integration

The initial Blog rendering implementation left `league/commonmark` unchanged because the implementation scope prohibited dependency updates. The blocker was remediated by the approved maintenance commit:

```text
5737412dd23e6828bd192cff2f15175f62185503
```

The maintenance commit updates the locked package from `league/commonmark` 2.8.2 to 2.10.0 and does not change `composer.json`.

The remediated advisories were:

- `GHSA-mj63-m3rc-8ppr`
- `GHSA-mh25-x5hq-wrqp`
- `GHSA-jfm3-95jq-q3rf`
- `GHSA-g2gp-3wwq-f4ph`
- `GHSA-2q4p-g7hv-5rgv`
- `GHSA-29pj-957v-52mc`

After integration, `composer audit --locked` must report no vulnerability advisories before release.

## Production Deployment Requirements

- Deploy application code and compiled assets together.
- Ensure route cache is rebuilt after deployment.
- Do not rely on `public/storage` for Blog header image delivery.
- No migration is required.
- No `.env` change is required.
- Review the existing `league/commonmark` advisories separately in a dependency-maintenance branch.
