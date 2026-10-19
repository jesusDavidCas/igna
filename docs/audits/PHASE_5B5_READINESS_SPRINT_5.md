# IGNA Studio Website Readiness Sprint 5

Status: partial local implementation. A bilingual public URL architecture needs
owner approval before it can be introduced.

## Verified Search Console evidence

The owner confirmed the `ignastudio.com` Domain property is verified, the
canonical sitemap was accepted, Google discovered 18 sitemap URLs, HTTPS signals
are present, and at least one inspected URL is indexed. This does not establish
that every discovered URL is indexed, that Google selected each declared
canonical, or that an English page has a separately indexed URL.

## Current architecture and reproduced limitation

Public routes use one language-neutral URL per resource. `SetLocale` resolves
the interface from a session value after routing; the locale form only saves
that session value. The same `/`, `/blog`, `/blog/{slug}`, and `/team/{slug}`
URLs can therefore render different interface language based on state, while
their canonical URLs and sitemap entries remain identical. There are no
language-specific public URLs and no `hreflang` annotations.

This prevents independent, stable English and Spanish crawl targets. The HTML
`lang` attribute and localized metadata describe the response that was rendered,
but cannot make a session-dependent alternate page discoverable.

## Safe correction implemented

The blog archive previously canonicalized every paginated result to `/blog`.
Page two and later now self-canonicalize as `/blog?page=N`, while tracking
parameters are excluded. The sitemap continues to list only canonical public
content, not login, tracking, private documents, proposals, markdown mirrors,
or excluded blog records. No `hreflang` is emitted because genuine
language-specific URLs do not exist.

## Bilingual URL alternatives

### A. Prefix both public languages

Use `/es/`, `/en/`, `/es/blog/{slug}`, and `/en/blog/{slug}`. This is symmetric
and clear but requires permanent redirects from every existing indexed public
URL, a full route and link migration, English translations of primary content,
and careful protection of non-public routes.

### B. Preserve current Spanish URLs and add English under `/en`

Treat existing public URLs as Spanish canonical targets: `/`, `/blog`,
`/blog/{slug}`, and `/team/{slug}`. Add only English equivalents under `/en`,
for example `/en/`, `/en/blog`, `/en/blog/{slug}`, and `/en/team/{slug}`.
This preserves discovered and indexed URLs, avoids a broad redirect migration,
and permits a real alternate URL only after its primary content is translated.

## Recommended owner decision

Choose alternative B. It minimizes disruption to established public URLs and
inbound links. After approval, the application should make legacy public URLs
deterministically Spanish rather than session-adaptive; language switching
should link to the equivalent English URL where it exists, not infer a locale
from a cookie. Keep authentication, tracking, signed document, proposal-token,
administration, privacy-preference, and API-like utility routes outside the
localized public route group.

Do not expose an English blog, team profile, or service URL until the primary
title, summary, body, and dynamic public content are genuinely available in
English. Existing dynamic Blog content does not yet provide persisted bilingual
primary fields, so an English route migration requires a separate content model
and translation plan.

## Future canonical, hreflang, and sitemap rules

- Each localized indexable response declares a self canonical for its own URL.
- Each pair emits reciprocal `hreflang="es"` and `hreflang="en"` links,
  including self references, only where both pages exist and primary content is
  genuinely translated.
- The stable Spanish legacy URL may be the `x-default` only after owner approval.
- The localized sitemap lists each canonical localized URL; add alternate
  annotations only once the reciprocal route set is complete.
- Blog page one canonicalizes to its archive path; later valid pages
  self-canonicalize with their page number. Do not include paginated archives in
  the sitemap unless a later crawl strategy specifically justifies that choice.
- Preserve query-free canonicals except for the valid blog `page` parameter.

## Required implementation surfaces after approval

Expected changes include public route definitions, locale middleware, the
locale-switch form, public navigation and pagination links, `SeoManager`, public
layout alternate-link rendering, sitemap generation/view, dynamic Blog and Team
translation data, SEO tests, and redirect/404 regression tests. No migration,
redirect, or `hreflang` was added in this sprint.

## Rollback and regression plan

Release the bilingual routing separately. Before rollout, inventory every
legacy public route and inbound URL; test 301 behavior only for approved moved
routes, direct 200 responses for both languages, reciprocal canonical and
`hreflang`, sitemap validity, pagination, 404 behavior, authentication,
tracking, proposals, signed credentials, and privacy preferences. Rollback
removes localized route exposure and restores only the prior canonical/link
logic; never redirect private or tokenized access paths through locale routes.

## Search Console validation after deployment

1. Confirm the Domain property remains verified and submit the generated
   sitemap URL only when its localized contents are live.
2. Inspect representative Spanish and English homepage, archive, article, and
   team URLs. Record indexing state, user-declared canonical, Google-selected
   canonical, crawl timestamp, and mobile Googlebot accessibility.
3. Check sitemap status and discovered/indexed counts; do not equate discovery
   with indexing.
4. Verify page-language pairs, reciprocal `hreflang`, canonical targets, and
   rendered content using URL Inspection live tests and HTML source.
5. Review Search performance by page, country, query, and device after enough
   post-release data exists. Google independently decides whether and when to
   index a technically valid URL.

## Local verification

`PublicPlatformTest` covers public metadata, noindex boundaries, sitemap
eligibility, canonical host redirects, paginated-blog canonical behavior,
absence of premature `hreflang`, and session-rendered HTML language attributes.
The focused class passed with 150 assertions. The full Laravel suite passed
with 1,836 assertions and no failures (207 inherited file-fixture warnings).
Composer validation and locked audit, the production-only npm audit, Vite build,
route-cache compilation, candidate Pint, release hygiene, and `git diff --check`
passed. The existing Node `DEP0205` warning remains inherited.

Disposable local browser review confirmed the same `/blog` URL renders Spanish
and then English interface text after session switching while retaining the same
Spanish article body, which reproduces the documented crawlability limitation.
Responsive browser coverage is inherited from Sprint 2; no production browser
or Search Console account report was accessed in this sprint.
