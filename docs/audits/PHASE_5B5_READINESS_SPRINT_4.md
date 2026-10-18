# IGNA Studio Website Readiness Sprint 4

Status: local technical foundation and legal-review drafts. No legal document
is published by this sprint.

## Evidence-backed processing inventory

| Activity | Data categories found in source | Purpose/system | Recipient or storage | Retention/basis status |
| --- | --- | --- | --- | --- |
| Public project request | Name, email, phone, project/location/description, preferred language, optional file | Ticket creation and project response | Application database, private document storage, administrators, transactional email | Retention and authorization wording require owner approval |
| Client account and login | Name, email, phone, role, password hash, session/remember state | Authentication and authorized portal access | Application database and session store | Retention requires owner approval |
| Proposals | Prospect/client contact, project and commercial data | Prepare and share a proposal | Application database, PDF output, signed/public access paths | Retention and sharing policy require owner approval |
| Tracking and documents | Ticket code, email, document metadata/content | Verify access, exchange and review project documents | Private storage; optional configured Google Drive integration | Processor/transfer and retention details require confirmation |
| Operational notifications | Recipient email, ticket/project context | Transactional request and document notifications | Configured mail transport and recipients | Mail processor and retention require confirmation |
| Server/security operations | Session identifier, CSRF state, authentication and application logs | Security, request integrity and operation | Session database/default Laravel logging | Hostinger/log retention requires confirmation |

The reviewed source contains an optional Google Drive integration, controlled
through configuration, but does not establish that it is enabled in production.
No GA4, Ads, Tag Manager, advertising pixel, or analytics script was found.

## Cookie inventory

| Category | Confirmed mechanism | Classification | Current treatment |
| --- | --- | --- | --- |
| Session | Laravel database session cookie, default 120-minute configuration | Strictly necessary | Required for state, locale, authenticated flows and CSRF-backed requests |
| CSRF | Laravel request token mechanism | Strictly necessary | Required for state-changing web forms |
| Authentication | Laravel session and optional remember-me mechanism | Strictly necessary | Used only after authentication actions |
| Analytics/advertising | None found | Not present | No tag or optional cookie is enabled |

## Implemented technical boundary

`config/privacy.php` keeps optional tracking disabled by default. The preference
route and accessible preference UI are only reachable/rendered if a future
approved deployment explicitly enables optional tracking. Preferences default to
denied, are not preselected, are stored in an HTTP-only, same-site cookie, and
are versioned. A future GA4 or Ads integration must use
`ConsentPreferences::allows()` before emitting any optional tag or conversion
event. This sprint installs no tag and sends no analytics data.

## Local verification

The dedicated privacy-preference feature tests pass for the dormant default,
unselected optional categories, explicit save and reject actions, invalid
actions and categories, and versioned expiry-aware deny-by-default behavior.
Malformed, expired, outdated, and truthy-but-not-boolean cookie values fail
closed. The full Laravel suite passes with 1,826 assertions and no failures
(205 inherited file-fixture warnings) under the documented disposable Spanish
locale test environment.
Composer validation, locked audit, and platform requirements pass; the
production-only npm audit reports zero vulnerabilities; the Vite production
build, route cache compilation, release-hygiene check, and diff whitespace
check pass. The existing Node `DEP0205` warning and development-only npm
advisories remain outside this sprint's dependency-free scope.

## Colombian legal framework - research notes

Law 1581 of 2012 establishes the general personal-data regime, including
confidentiality and the rules for personal-data processing. SIC guidance states
that a policy must exist in a physical or electronic medium and be made known to
data subjects; it also describes the privacy notice as the means to inform the
holder about the policy, access method, and intended purpose. SIC guidance
states that silence, preselected boxes, and inaction do not constitute valid
consent, and that acceptance of a privacy policy alone does not by itself prove
authorization.

Decree 1074 of 2015 contains the policy-content and data-subject request-channel
rules referenced by SIC. RNBD applicability cannot be determined from source:
the owner must confirm legal form, chamber registration, assets, and applicable
thresholds. Even an entity not required to register remains subject to the
general obligations described by SIC, including a policy, notice, authorization
where needed, procedures for requests, and security measures.

Authoritative sources reviewed: Ley 1581 in Función Pública's Gestor Normativo;
SIC guidance on policies, notices, consent, and the RNBD. Legal counsel must
validate the final application to the business's facts.

## WEB status

| Requirement | Status | Reason |
| --- | --- | --- |
| WEB-01 Privacy policy | PARTIALLY IMPLEMENTED | Reviewable Spanish draft and inventory exist; identity/contact/retention/legal approval block publication |
| WEB-02 Terms and conditions | PARTIALLY IMPLEMENTED | Reviewable draft exists; business terms and legal approval are pending |
| WEB-05 Cookie inventory and consent | IMPLEMENTED AND VERIFIED LOCALLY | Current necessary-cookie inventory and disabled-by-default optional-consent boundary are source-tested; production configuration still needs verification |

## Required owner and legal decisions

1. Confirm controller identity, identification, address, privacy contact, and
   data-subject request channel.
2. Approve documented purposes, retention/deletion periods, processor list,
   international transmission/transfer assessment, and the authorization text.
3. Determine RNBD applicability with Colombian counsel or the responsible
   compliance function.
4. Approve any GA4, Ads, or conversion-measurement design before enabling the
   optional-tracking configuration.
