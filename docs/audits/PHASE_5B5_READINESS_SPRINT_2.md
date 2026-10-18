# Website Readiness Sprint 2

Date: 2026-10-18
Base: `8ef07b9d30428c6484285276a6e81045656a52f0`
Branch: `codex/phase-5b5-readiness-sprint-2`

## Scope and evidence boundary

This sprint reviewed the open, source-supported items WEB-10 through WEB-14
and WEB-16. It does not change the public URL architecture, data model,
authentication, uploads, analytics, cookies, privacy content, or production
configuration. No deployment, production mutation, or authenticated crawl was
performed.

## Broken-link crawl

On 2026-10-18, a read-only, rate-limited crawl verified the public homepage,
robots resource, sitemap, 18 sitemap URLs, and 40 unique same-origin public
destinations discovered from the homepage, published blog pages, team pages,
tracking page, versioned images, favicon, manifest, and built CSS and JavaScript
assets. All checked destinations returned HTTP 200 after ordinary redirects.

The crawl found no confirmed 404, 410, redirect chain, missing public image,
missing asset, invalid canonical destination, or same-document anchor failure.
Signed credentials, authenticated routes, forms, and private routes were
excluded. External-link availability was not treated as a defect because no
demonstrably unavailable public external destination was discovered.

This is a production snapshot, not evidence of an ongoing link-health process.
Periodic crawl ownership remains open.

## Accessibility and image delivery

The public hero artwork has an informative accessible name while its nested
fallback image is decorative. Team-photo markup supplies the member name when
a photo exists and a labeled initials fallback when it does not. Blog header
images still use the current title as their available CMS fallback; creating
editorial image descriptions requires a reviewed content-model decision and was
not inferred from article titles in this sprint.

Measured source color pairs were:

| Foreground / surface | Ratio | Result |
| --- | ---: | --- |
| `olive-700` on paper | 7.23:1 | AA pass |
| `olive-800` on paper | 8.91:1 | AA pass |
| `stone-600` on paper | 6.46:1 | AA pass |
| `stone-700` on paper | 8.70:1 | AA pass |
| `stone-500` on paper | 4.06:1 | Fail for ordinary text |
| `olive-200` on `olive-700` | 5.59:1 | AA pass |
| `stone-100` on `olive-700` | 7.82:1 | AA pass |
| white on `olive-700` | 8.53:1 | AA pass |

The article date and empty-blog state used the failing `stone-500`/paper pair.
They now use `stone-600`. Other reviewed `stone-500` uses remain on white or
stone surfaces, where their measured contrast is not part of this paper-canvas
defect.

Profile photos are top-of-page content and were marked `loading="eager"` with
`fetchpriority="high"`. Homepage team cards continue to use lazy loading.
Width and height attributes remain present, protecting against layout shift.
No original uploaded image, derivative, storage service, or image route changed.

## Responsive and local browser checks

The local app used a disposable SQLite database seeded only with synthetic
records. At 1440x900, 768x1024, 390x844, and 375x812, document scroll width did
not exceed viewport width. Each homepage render had one main landmark and the
mobile navigation disclosure revealed its six expected public links. No form
was submitted.

Normal English and Spanish locale behavior remains covered by the existing
feature test. The disposable browser's session switch did not persist reliably
enough to call it manual Spanish-browser evidence, so this report does not do
so. The Sprint 1 custom 404 remains covered by `PublicPlatformTest`.

## Performance evidence

The production bundle produced in this isolated worktree contains a 16.06 kB
gzipped CSS asset and a 0.24 kB gzipped application JavaScript asset. This is
a build artifact measurement, not a page-speed measurement. No comparable
three-run lab dataset, field LCP, field INP, field CLS, or CrUX/Search Console
data was available; WEB-12 remains `NOT VERIFIED`.

## Files changed

- `resources/views/components/team/photo.blade.php`
- `resources/views/public/blog/show.blade.php`
- `resources/views/public/home.blade.php`
- `tests/Feature/TeamPhotoDeliveryTest.php`
- `tests/Feature/BlogPublishingRenderingTest.php`
- `docs/audits/PHASE_5B5_WEBSITE_READINESS_MATRIX.md`
- `docs/audits/PHASE_5B5_READINESS_SPRINT_2.md`

## Verification

- `TeamPhotoDeliveryTest`: passed with 64 assertions and inherited fixture warnings.
- `BlogPublishingRenderingTest`: passed with 88 assertions and inherited fixture warnings.
- `PublicPlatformTest`: passed with 124 assertions and inherited fixture warnings.
- The full Laravel suite passed with 1,733 assertions, 196 inherited
  fixture-file warnings, and no failures when run with the documented
  `APP_LOCALE=es` default. This isolated worktree intentionally has no `.env`;
  Laravel otherwise defaults to English, which exposes two pre-existing
  Spanish-string assumptions in `AdminOperationsTest`. The same two focused
  tests pass on both the Sprint 1 base and this branch with `APP_LOCALE=es`.
  They are not caused by this sprint. Reproduce the documented environment
  without reading or changing an `.env` file with:
  `APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' APP_ENV=testing APP_LOCALE=es php artisan test`.
  Explicit `config()->set('app.locale', 'es')` setup inside those inherited
  tests would be more self-contained, but changing unrelated baseline tests is
  outside this sprint.
- `composer validate`, `composer audit --locked`, and
  `composer check-platform-reqs` passed. The locked Composer audit reported no
  security advisories.
- `npm audit --omit=dev` reported zero production vulnerabilities. `npm ci`
  reported six development-only advisories; no dependency was changed in this
  sprint.
- `npm run build` passed. The existing Node `DEP0205` deprecation warning was
  observed and is not attributable to this source change.
- Candidate-only Pint, route-cache compilation and subsequent cache clear, and
  `git diff --check` passed.
- Composer dependencies and frontend dependencies were installed from their
  existing lockfiles solely to bootstrap this isolated worktree; no lockfile
  changed.

## Remaining requirements

- A full WCAG 2.2 AA audit still needs interactive-state, assistive-technology,
  browser, zoom, and authenticated/client coverage.
- Editorial image alternatives need a content-model and CMS workflow decision.
- Image budget, responsive derivative strategy, cache telemetry, and comparable
  laboratory/field Core Web Vitals remain pending.
- Production link health needs recurring ownership; the Sprint 2 crawl is only
  a successful snapshot.

## Release recommendation

The narrowly corrected source is suitable for human acceptance review. It is
not a production-readiness declaration: live Core Web Vitals, comprehensive
accessibility conformance, and recurring link-health operations remain outside
this sprint.
