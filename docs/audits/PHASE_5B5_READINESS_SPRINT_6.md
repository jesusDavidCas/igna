# IGNA Studio Website Readiness Sprint 6

Status: partial local implementation. The public URL architecture is in place,
but no English page is published by default because no owner-approved complete
English primary-content record currently exists.

## Exact baseline

- Worktree branch: `codex/phase-5b5-readiness-sprint-6`
- Starting commit: `8a4960ba7dce730ccec200835f8b7640c5db97eb`
- Spanish public URLs retained: `/`, `/blog`, `/blog/{slug}`, and
  `/team/{slug}`.
- New, separately named English routes: `/en/`, `/en/blog`,
  `/en/blog/{slug}`, and `/en/team/{slug}`.

No existing authentication, tracking, portal, proposal, signed-document,
credential, administration, privacy-preference, image, favicon, or SEO utility
route was moved into the English group.

## Locale architecture and guarantees

The former global `SetLocale` middleware remains in place for existing
application workflows. `SetPublicLocale` is route-scoped and runs after it for
the localized public groups, making the URL authoritative:

| URL class | Locale source | Session / Accept-Language effect |
| --- | --- | --- |
| Existing Spanish public pages | Route middleware (`es`) | Cannot override the route |
| `/en` public namespace | Route middleware (`en`) | Cannot override the route |
| Tracking, login, portal, admin, proposals, signed files | Existing behavior | Unchanged |

There is no generic fallback route. Native Laravel unmatched-route 404 and
unsupported-method behavior remains intact.

## Publication eligibility source of truth

`config('igna.seo.english_publication.blog_slugs')` is an explicit publication
registry and is empty by default. It is not a fallback, language detector, or
automatic translation mechanism. A Blog entry can be admitted only when all of
the following are true:

1. It is already eligible as a published Spanish Blog entry.
2. Its curated English language resource contains nonempty title, summary, and
   primary body values.
3. Its slug appears in the explicit owner-approved registry.

The existing `demo.blog.*` English values are curated source strings, but their
presence alone does not publish an article. The registry stays empty in this
sprint because no editorial approval evidence was supplied.

Team records have only single-locale persisted role, summary, biography, and
expertise fields. Although the development seeder has English source strings,
it writes one locale into those fields and carries no approval state. The
homepage renders Team and Blog primary content, so it too remains unavailable
in English. A durable bilingual Team data model and owner approval are required
before either `/en/` or `/en/team/{slug}` can be made indexable.

## Publication matrix

| Page | Spanish eligible | English eligible now | Canonical | Hreflang | Sitemap | Reason |
| --- | --- | --- | --- | --- | --- | --- |
| `/` | Yes | No | Spanish self | None | Spanish only | English homepage cannot prove complete approved Team/Blog content |
| `/blog` | Yes | No | Spanish self, valid `?page=N` self-canonical | None | Spanish only | Registry has no approved complete English article |
| `/blog/{slug}` | Published posts only | No | Spanish self | None | Spanish published posts | No approved English Blog slug |
| `/team/{slug}` | Active profiles only | No | Spanish self | None | Spanish active profiles | No persisted English Team content or approval state |
| `/en/` | N/A | 404 | None | None | Excluded | Fail closed |
| `/en/blog` | N/A | 404 | None | None | Excluded | Fail closed |
| `/en/blog/{slug}` | N/A | 404 unless all Blog gates pass | English self when eligible | Reciprocal when eligible | Eligible posts only | Fail closed |
| `/en/team/{slug}` | N/A | 404 | None | None | Excluded | Fail closed |

## Canonical, alternate, sitemap, and metadata behavior

- Spanish route canonicals remain stable and tracking parameters are excluded.
- Both `/blog?page=N` and an eligible `/en/blog?page=N` self-canonicalize.
- An out-of-range archive page returns 404 instead of an indexable empty page.
- Only a genuine Spanish/English Blog pair emits absolute HTTPS `es` and `en`
  HTML alternate links; no `x-default` was added.
- Every eligible English Blog page has its own canonical, Open Graph URL,
  JSON-LD page URL, and English language code. It never canonicalizes to the
  Spanish article.
- The sitemap includes English Blog URLs only after the same eligibility gate.
  It never contains paginated archives, private links, draft/unapproved content,
  tracking, authentication, signed files, or legal drafts.
- `robots.txt` remains permissive for legitimately published public pages and
  continues to point to the existing sitemap.

## Local translated-content evidence

Static interface copy exists in both language files. Service records support
bilingual fields and seed data contains English service text. That alone does
not authorize the English homepage because its team and Blog sections depend on
separate primary content. Three curated `demo.blog.*` English source entries
exist, but no published content-owner approval was supplied and one matching
seeded entry is a draft. No English Team profile can be established safely from
the existing single-locale table.

## Required owner and data-model decisions

1. Approve each complete English Blog article and add it through the reviewed
   registry only after editorial review.
2. Approve a dedicated additive schema and administration workflow for English
   Team role, summary, biography, expertise, and publication state. Do not
   overwrite Spanish fields or infer translations from seed strings.
3. After Team content is complete and approved, define a homepage publication
   rule that requires every visible dynamic section to have eligible English
   content before enabling `/en/`.
4. Decide whether the temporary configuration registry should be replaced by a
   persistent editorial publication model for Blog as well. That is a separate
   schema approval, not part of this sprint.

## Automated local verification

`PublicPlatformTest` passed with 178 assertions and no failures. It verifies
Spanish route determinism against session and `Accept-Language`, fail-closed
English routes, native 404/405 preservation, approved English Blog rendering,
reciprocal alternates, canonical URLs, sitemap eligibility, tracking-parameter
exclusion, malformed publication configuration, and out-of-range pagination
404s. Eligible English rendering uses a synthetic runtime translation fixture;
no actual article is enabled by the test. The run reported 20 inherited
file-fixture warnings. No email is sent by these tests.

The focused cross-sprint regression band passed with 418 assertions and 53
inherited file-fixture warnings. The complete Laravel suite passed with 1,862
assertions and 209 inherited warnings. `composer validate`, `composer audit
--locked`, `composer check-platform-reqs`, `npm audit --omit=dev`, `npm run
build`, route-cache compilation, candidate-only Pint, release-hygiene, and
`git diff --check` all passed. The build emitted only the inherited Node
DEP0205 warning.

Browser smoke validation used a disposable local SQLite database at
`127.0.0.1:8016`: the Spanish homepage rendered as Spanish, retained its
Spanish service groups and public links, and exposed no unavailable English
switch. `/en/` rendered the branded, accessible English 404 without redirect
or Spanish primary-content leakage. No production service, Google Search
Console account, Hostinger environment, or live data was accessed.

## Post-deployment Search Console checklist

After a separately approved release with actual English content, inspect each
published Spanish/English pair in Search Console: HTTP status, user-declared
canonical, Google-selected canonical, rendered HTML `lang`, reciprocal
alternates, mobile Googlebot accessibility, and sitemap discovery. Keep the
verified Domain property and submit the existing sitemap only after the URL is
live. Discovery is not indexing; record page-level results without claiming
that Google has indexed every URL.
