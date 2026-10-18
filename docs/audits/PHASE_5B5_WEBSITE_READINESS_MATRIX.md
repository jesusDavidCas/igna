# IGNA Studio Website Readiness Matrix

Date: 2026-10-18

## Evidence boundary

This local implementation snapshot is based on production baseline
`4b6fd644f46fa43afd141397093f499e9082c8ee` and the recovered Phase 5B.5
audit material. It does not represent a production crawl, an approval of legal
content, a Hostinger review, or live analytics/Search Console evidence.

| ID | Requirement | Status after sprint 1 | Priority | Dependency / next step |
| --- | --- | --- | --- | --- |
| WEB-01 | Privacy policy | NOT IMPLEMENTED | P1 | Legal and product decisions |
| WEB-02 | Terms and conditions | NOT IMPLEMENTED | P1 | Legal and product decisions |
| WEB-03 | Secret exposure | IMPLEMENTED LOCALLY | P1 | Run the tracked-content hygiene gate in each release check; CI enforcement remains an operational decision |
| WEB-04 | HTTPS and transport | NOT VERIFIED | P1 | Hostinger edge evidence |
| WEB-05 | Cookies and consent | PARTIALLY IMPLEMENTED | P1 | Legal basis and analytics decision |
| WEB-06 | Titles and descriptions | PARTIALLY IMPLEMENTED | P2 | Locale/crawl strategy |
| WEB-07 | Social previews | PARTIALLY IMPLEMENTED | P2 | Approved raster brand asset |
| WEB-08 | Favicon | PARTIALLY IMPLEMENTED | P2 | Live MIME/device check |
| WEB-09 | Sitemap and robots | PARTIALLY IMPLEMENTED | P2 | Live delivery and Search Console |
| WEB-10 | Image alternatives | IMPLEMENTED LOCALLY | P2 | CMS media inventory and assistive-technology sample |
| WEB-11 | Image compression and delivery | PARTIALLY IMPLEMENTED | P2 | Measured image budget and production transfer evidence |
| WEB-12 | Loading speed / CWV | NOT VERIFIED | P2 | Comparable lab runs and field-data source |
| WEB-13 | Color contrast | PARTIALLY IMPLEMENTED | P2 | Full state-by-state WCAG 2.2 AA audit |
| WEB-14 | Mobile responsiveness | PARTIALLY IMPLEMENTED | P2 | Wider public/auth/client browser matrix |
| WEB-15 | Localized custom 404 | IMPLEMENTED LOCALLY | P2 | Human visual review and production smoke |
| WEB-16 | Broken links | PARTIALLY VERIFIED - PRODUCTION SNAPSHOT | P2 | Recurring crawl ownership and external-link monitoring |
| WEB-17 | Form validation | IMPLEMENTED LOCALLY | P1 | Preserve negative test coverage |
| WEB-18 | Anti-spam | IMPLEMENTED LOCALLY | P1 | Phase 5B.5A Hostinger IP evidence |
| WEB-19 | GA4 | NOT IMPLEMENTED | P2 | Legal approval and measurement contract |
| WEB-20 | Calls to action | PARTIALLY IMPLEMENTED | P2 | Analytics and content decision |
| OPS-01 | Authentication/session security | IMPLEMENTED LOCALLY | P1 | Production smoke evidence |
| OPS-02 | XSS/CSRF/SQL/file safety | IMPLEMENTED LOCALLY | P1 | Preserve security regression band |
| OPS-03 | Dependency security | IMPLEMENTED LOCALLY | P1 | CI recurrence; dev-tool advisory remediation |
| OPS-04 | Retention/database integrity | NOT IMPLEMENTED | P1 | Legal/business retention decisions |
| OPS-05 | Backups/disaster recovery | PARTIALLY IMPLEMENTED | P1 | Restore-drill evidence |
| OPS-06 | Monitoring/alerts/CI | NOT IMPLEMENTED | P1 | Tooling and ownership decisions |
| OPS-07 | Deployment/rollback | PARTIALLY IMPLEMENTED | P1 | Approved release preflight |
| OPS-08 | Email reliability | PARTIALLY IMPLEMENTED | P2 | Operational latency/failure evidence |

## Sprint 1 change

WEB-15 moved from `NOT IMPLEMENTED` to `IMPLEMENTED LOCALLY`. The result is a
branded, keyboard-reachable error page returned by Laravel's native 404
handling. The initial `GET|HEAD` fallback was removed because it changed
unknown POST requests from the baseline 404 to 405. Unknown-method behavior is
now baseline-compatible. Since native unmatched-route handling precedes the
web middleware group, the page uses the configured application locale when a
session locale is unavailable; session-specific locale matching for unknown
URLs is deferred to future bilingual SEO/localization work. This is not a
production-verification claim.

## Sprint 2 change

WEB-10 to WEB-14 and WEB-16 received a constrained follow-up. A rate-limited,
read-only public crawl on 2026-10-18 found 200 responses for all 18 sitemap
URLs and the 40 same-origin public destinations, assets, and images discovered
from the homepage, blog, team, tracking, robots, and sitemap surfaces. No
confirmed broken internal link, resource, redirect chain, or anchor was found.
This is a point-in-time production observation, not a recurring link-health
program.

Two contrast defects on the paper canvas were corrected locally: published
article metadata and the empty-blog message now use `stone-600` (6.46:1 on the
paper surface) instead of `stone-500` (4.06:1). Team-profile photos now load
eagerly with high fetch priority while card and list images remain lazy. These
changes preserve intrinsic dimensions and private-image delivery routes.

Local browser checks at 1440, 768, 390, and 375 pixel widths found no horizontal
overflow. The mobile navigation disclosure exposed its six intended public
links. Automated localization coverage remains the evidence for normal English
and Spanish rendering; no production locale-routing claim is made. There is no
comparable lab or field Core Web Vitals dataset, so WEB-12 remains `NOT
VERIFIED`.

## Sprint 3 change

Sprint 3 adds a local release-hygiene gate and applies immutable caching only
to the exact current version of a public blog-header image. Missing or stale
versions revalidate, and the public image endpoint rejects unpublished posts.
Sprint 3 prioritizes the article lead image and makes public request/tracking
form labels and validation feedback programmatically accessible. WEB-03 is now
`IMPLEMENTED LOCALLY`; WEB-11, WEB-13, and WEB-14 remain partial and
WEB-04/WEB-12 retain their external infrastructure and measurement
dependencies. See `PHASE_5B5_READINESS_SPRINT_3.md` for evidence.
