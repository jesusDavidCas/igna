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
| WEB-03 | Secret exposure | PARTIALLY IMPLEMENTED | P1 | CI secret-scan decision |
| WEB-04 | HTTPS and transport | NOT VERIFIED | P1 | Hostinger edge evidence |
| WEB-05 | Cookies and consent | PARTIALLY IMPLEMENTED | P1 | Legal basis and analytics decision |
| WEB-06 | Titles and descriptions | PARTIALLY IMPLEMENTED | P2 | Locale/crawl strategy |
| WEB-07 | Social previews | PARTIALLY IMPLEMENTED | P2 | Approved raster brand asset |
| WEB-08 | Favicon | PARTIALLY IMPLEMENTED | P2 | Live MIME/device check |
| WEB-09 | Sitemap and robots | PARTIALLY IMPLEMENTED | P2 | Live delivery and Search Console |
| WEB-10 | Image alternatives | IMPLEMENTED LOCALLY | P2 | CMS media inventory |
| WEB-11 | Image compression | PARTIALLY IMPLEMENTED | P2 | Measured media budget |
| WEB-12 | Loading speed / CWV | NOT VERIFIED | P2 | Lab and field measurements |
| WEB-13 | Color contrast | NOT VERIFIED | P2 | WCAG 2.2 AA audit |
| WEB-14 | Mobile responsiveness | PARTIALLY IMPLEMENTED | P2 | Wider public/auth/client browser matrix |
| WEB-15 | Localized custom 404 | IMPLEMENTED LOCALLY | P2 | Human visual review and production smoke |
| WEB-16 | Broken links | NOT VERIFIED | P2 | Public crawl access and ownership |
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
