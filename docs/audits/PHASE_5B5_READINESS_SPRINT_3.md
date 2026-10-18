# IGNA Studio Website Readiness Sprint 3

Date: 2026-10-18

## Scope

Sprint 3 starts at Sprint 2 commit
`71f08fb89f7d1527228e89aa0512f477500428ae`. It is a local, uncommitted
implementation pass. It does not change legal content, consent, analytics,
bilingual URLs, the Phase 5B.5A anti-spam candidate, Hostinger configuration,
production data, or dependencies.

## Reproduced evidence

### Production network sample

Read-only HTTPS requests were made once under the same local network conditions
on 2026-10-18. They are transfer observations, not Lighthouse or field Core
Web Vitals measurements.

| Public URL | HTTP | HTML bytes | TTFB | Total |
| --- | ---: | ---: | ---: | ---: |
| `/` | 200 | 60,108 | 1.896s | 2.333s |
| `/blog` | 200 | 27,011 | 0.871s | 1.082s |
| Published blog article | 200 | 15,078 | 0.911s | 0.912s |
| Team profile | 200 | 13,306 | 0.805s | 0.805s |
| `/tracking` | 200 | 10,107 | 0.897s | 0.897s |

The homepage was the slowest sampled document. Its server response time cannot
be corrected safely in Laravel without Hostinger runtime and cache evidence.
The anonymous PageSpeed API returned rate-limit status 429, so no Lighthouse,
LCP, CLS, TBT, or INP value is claimed.

Three subsequent homepage samples measured 1.920s, 0.903s, and 0.807s TTFB
(median 0.903s). This small sequential sample does not show consistently high
homepage TTFB; it shows variable server/network timing and is not a substitute
for controlled performance diagnostics.

### Source-confirmed defects

1. Public blog-header URLs are versioned when the post changes, but their
   response allowed only seven days of browser caching. This left safe cache
   reuse on the table.
2. The public article's lead image had no priority hint even though it is a
   likely LCP candidate. Archive cards correctly remain lazy.
3. The public request and tracking forms displayed labels but did not associate
   them with their controls. Server validation errors were visible but lacked a
   live alert role and deterministic focus target.

## Implemented corrections

| Area | Correction | Local acceptance evidence |
| --- | --- | --- |
| Browser image cache | Only an exact current blog-header version receives `public, max-age=31536000, immutable`; missing or stale versions use `no-cache` and revalidate. An updated post produces a new versioned URL. | Route regression covers current, missing, stale, and conditional ETag requests. |
| Article loading priority | The article lead image uses `fetchpriority=high`; archive images explicitly remain without it and remain lazy. | Public rendering regression asserts both states. |
| Accessible public forms | Request and tracking labels now use stable `for`/`id` pairs. Relevant help text is connected with `aria-describedby`; appropriate browser autocomplete tokens are present. | Public platform regression asserts request and tracking markup. |
| Validation feedback | Success uses a polite status role. Validation output is an alert and receives focus after the response is rendered. | Public request validation regression asserts the live summary marker. |
| Release hygiene | Added a local tracked-content gate for whitespace, tracked environment artifacts, and high-signal credential patterns. It reports file paths only. | Shell syntax plus safe and synthetic-failure PHPUnit fixtures. |

## Security and infrastructure boundary

The production observation confirms HTTPS, secure cookies, the existing
baseline headers, and a CSP that only upgrades insecure requests. HSTS was not
observed. Enabling HSTS, changing CSP, suppressing PHP web-server headers, or
changing compression/cache policy at Hostinger requires the separate server
configuration review. No restrictive policy was guessed from source.

The hygiene check is a source-release gate, not a claim that Git history,
third-party systems, ignored local files, or production secrets have been
scanned. It is intentionally scoped so it does not print suspected secret
values.

The public blog-header endpoint now independently verifies that its post is
published before it serves an image. Missing, invalid, private-path, and draft
media remain non-cacheable 404 responses.

## Requirement status updates

| Requirement | Sprint 3 status |
| --- | --- |
| WEB-03 Secret exposure prevention | IMPLEMENTED AND VERIFIED LOCALLY |
| WEB-04 HTTPS and transport | PARTIALLY IMPLEMENTED; Hostinger CSP/HSTS/redirect evidence remains required |
| WEB-11 Image delivery and cache efficiency | PARTIALLY IMPLEMENTED; versioned blog images now cache for one year, while deployed transfer budgets and responsive variants remain unmeasured |
| WEB-12 Performance and CWV | BLOCKED — EXTERNAL EVIDENCE REQUIRED; PageSpeed rate limited and no field-data source is available |
| WEB-13 WCAG 2.2 AA | PARTIALLY IMPLEMENTED; public form labels and error announcement/focus are remediated, but a full state and assistive-technology review remains required |
| WEB-14 Responsive UX | PARTIALLY IMPLEMENTED; semantic changes preserve the existing responsive layout, while the wider device matrix remains required |

## Search Console evidence

Owner-supplied Google Search Console evidence confirms that the domain property
for `ignastudio.com` is verified, sitemap submission succeeded, and the sitemap
reported 18 discovered URLs on 2026-10-18. At least one inspected URL was
reported as indexed. This confirms property and sitemap setup only; it does not
mean that all 18 URLs are indexed, does not install GA4, and does not resolve
the separate bilingual URL and `hreflang` architecture decision.

## Verification

The focused band for public rendering, blog image delivery, release hygiene,
content security, and team-photo delivery passed with 372 assertions. The
complete Laravel suite passed with 1,797 assertions and no failures. PHPUnit
emitted 199 inherited SQLite file-read warnings in the full run.

The following also passed:

- `composer validate`
- `composer audit --locked`
- `composer check-platform-reqs`
- `npm audit --omit=dev`
- `npm run build`
- `php artisan route:cache` followed by `php artisan route:clear`
- candidate-only Pint
- `bash -n scripts/verify-release-hygiene.sh`
- `bash scripts/verify-release-hygiene.sh`
- `git diff --check`

The full npm audit reports six development-toolchain advisories. The
production-only audit reports zero vulnerabilities; no dependency update was
made in this sprint. The build reports the existing Node `DEP0205` deprecation
warning and produces the expected manifest and CSS/JavaScript assets. A local,
disposable SQLite browser pass confirmed Spanish public form labels, native
invalid-field focus, no horizontal overflow at 390 pixels, no console errors,
and the branded Spanish 404's `noindex, nofollow` response metadata.

## Remaining human actions

1. Run comparable PageSpeed/Lighthouse mobile and desktop diagnostics after
   deployment access is available; collect three runs per public view and do
   not treat them as field CWV.
2. Obtain Hostinger evidence for HTTPS redirects, HSTS policy, CSP
   compatibility, compression, cache headers, and real client-IP topology.
3. Complete the wider keyboard, screen-reader, zoom/reflow, and touch-target
   review for public, authentication, and client workflows.
4. Keep legal, consent, Google Search Console, GA4, Ads, and bilingual URL
   work in their separately approved packages.
