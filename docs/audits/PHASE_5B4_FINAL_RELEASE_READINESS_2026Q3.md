# Phase 5B.4 Final Release Readiness

Date: 2026-08-15

Feature branch: `fix/blog-publishing-rendering`

Release branch: `release/phase-5b4-2026q3`

Phase 5B.3 baseline: `3b643313608fab959c25727f20b4e92baeee608f`

Blog implementation commit: `8cb60bc12ea667d2d22f7d67adc1c1fdb68587e9`

CommonMark security source commit: `5737412dd23e6828bd192cff2f15175f62185503`

CommonMark integration commit on feature branch: `3d8be9375cda566b9d0b9c8d4a8193eb40e19cad`

## Scope

Phase 5B.4 repairs Blog publishing presentation, serves Blog header images through a validated route-backed controller, integrates the approved CommonMark security lockfile update, and prepares the feature for Hostinger deployment.

This phase does not deploy production, access Hostinger, change `.env`, add migrations, or perform broad dependency updates.

## Blog Rendering

- Public Blog article pages render sanitized editorial HTML inside `.blog-article-content`.
- Paragraphs, H2, H3, lists, links, blockquotes, emphasis, inline code, and responsive images have explicit article typography.
- Public Blog index cards, article hero images, Open Graph images, and admin preview use `BlogPost::headerImageUrl()`.
- Public views no longer depend on `/storage/blog/headers/...`.

## Header Image Security

- Blog header images are delivered through `GET /blog/{post:slug}/header-image`.
- Route name: `blog.header-image`.
- Only stored paths under `blog/headers/` are eligible.
- Traversal, absolute paths, backslashes, control characters, unrelated directories, invalid images, missing files, and private credential-style paths fail closed.
- Valid JPEG, PNG, and WebP files return detected MIME type, `Content-Length`, `ETag`, public cache headers, and `X-Content-Type-Options: nosniff`.
- Header-image uploads are validated for image content, extension, MIME type, dimensions, and size.

## Security Maintenance

- The only dependency change is `composer.lock`.
- `league/commonmark` is locked to 2.10.0.
- `composer.json` is unchanged.
- The integration resolves the previously reported CommonMark advisories.

## Quality Gate

The final release gate must include:

- `composer show league/commonmark`
- `composer validate`
- `composer audit --locked`
- `composer check-platform-reqs`
- `php artisan test --filter=Blog`
- `php artisan test --filter=Html`
- `php artisan test --filter=Sanit`
- `php artisan test --filter=Proposal`
- `php artisan test --filter=TeamPhotoDeliveryTest`
- `php artisan test`
- `npm audit --omit=dev`
- `npm run build`
- `php artisan route:list --except-vendor`
- `git diff --check`
- PHP syntax checks on changed PHP files
- Browser smoke check for the public Blog article and routed header image
- Graphify refresh and smoke queries

## Migration Requirement

Phase 5B.4 adds no migration.

## Production Prerequisites

- `storage/app/public/blog/headers` must exist or be creatable.
- `storage/app/public/blog/headers` must be writable by the PHP runtime.
- Laravel routing must reach `/blog/{slug}/header-image`.
- Deploy source and compiled public assets together.
- Rebuild Laravel caches after deployment.

## Release Verdict

Phase 5B.4 is eligible for release after the final quality gate, feature branch push, release branch push, and public artifact verification pass.
