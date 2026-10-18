# Website Readiness Sprint 1

Date: 2026-10-18
Base: `4b6fd644f46fa43afd141397093f499e9082c8ee`
Branch: `codex/phase-5b5-readiness-sprint-1`

## Scope selected from the Phase 5B.5 audit

Only WEB-15 was both source-confirmed and independent of external policy or
production evidence. The audit found correct framework 404 status handling but
no branded `errors/404` view.

## Remediation

`resources/views/errors/404.blade.php` supplies a localized IGNA Studio error
page with a skip link, landmarked main content, homepage and blog recovery
links, focus-visible controls, responsive spacing, and `noindex, nofollow`.
Laravel's native `NotFoundHttpException` rendering selects this error view
without a wildcard route. JSON misses retain Laravel's normal JSON 404
handling.

## Corrected routing policy

The initial candidate used a `GET|HEAD` fallback route. It made an unknown
`POST` match a known URI with an unsupported method and changed the baseline
response from 404 to 405. The fallback was removed.

The corrected candidate matches the baseline response matrix: unknown GET,
HEAD, POST, PUT, PATCH, DELETE, and OPTIONS requests return 404; POST to the
existing GET-only `/admin` endpoint remains 405. Native unmatched-route
handling happens before the `web` middleware group, so an unmatched HTML 404
uses the configured application locale rather than the session-selected locale.
This is an intentional Sprint 1 limitation. The existing locale switch and
normal English/Spanish pages remain unchanged. Session-specific unknown-route
localization is deferred to a future reviewed bilingual SEO/localization
architecture, not solved by moving session middleware globally.

## Before and after evidence

Before: a missing public URL returned the framework error template.

After: `PublicPlatformTest` asserts native 404 handling, recovery links, the
noindex directive, JSON 404 output, the complete unknown-method matrix, an
existing-route 405, and Blog/Team missing-resource 404s. It also records the
configured-locale policy when a session locale is unavailable. Local browser
inspection of the English page confirmed the expected heading, semantic main
region, skip link, recovery actions, noindex directive, and no console errors.
At widths 1440, 768, and 375 pixels, document scroll width matched client
width.

The local plain HTTP server cannot complete the application's HTTPS-enforced
language-switch POST. Existing automated locale-switch coverage continues to
verify Spanish and English normal public pages; unmatched 404 session-locale
matching is deliberately not claimed. This is not production verification.

## Verification

- `php artisan test --filter=PublicPlatformTest`: passed, 15 tests, 124 assertions, 15 inherited fixture warnings.
- `php artisan test --filter=ReleaseContentSecurityTest`: passed, 4 tests, 32 assertions, 4 inherited fixture warnings.
- `php artisan test --filter=AuthenticationRevocationTest`: passed, 6 tests, 39 assertions, 6 inherited fixture warnings.
- `php artisan test --filter=AuthRedirectTest`: passed, 3 tests, 6 assertions, 3 inherited fixture warnings.
- `php artisan route:cache`: passed; the cache was cleared after verification.
- `vendor/bin/pint --test`: passed for changed PHP files.
- `composer validate`: passed.
- `composer audit --locked`: no advisories.
- `composer check-platform-reqs`: passed on PHP 8.5.8.
- `npm audit --omit=dev`: zero production vulnerabilities.
- `npm run build`: passed; Vite produced the manifest and assets. Node emitted its known `module.register()` deprecation warning.
- `git diff --check`: passed.

`npm ci` reported six development-only advisories (four high, two critical);
this sprint made no dependency changes.

## Remaining blockers and release order

Legal pages, privacy/cookie decisions, live HTTPS/header validation, field or
lab performance measurements, contrast audit, link crawl, Search Console,
session-specific locale matching for unmatched URLs, and Phase 5B.5A Hostinger
client-IP verification remain outside this sprint.

Review this isolated change first. It is independent of the protected Phase
5B.5A worktree and can be reviewed or released before it; merge/rebase only
after that worktree has its own approved checkpoint. A production release still
requires normal production smoke checks for the 404 response and assets.
