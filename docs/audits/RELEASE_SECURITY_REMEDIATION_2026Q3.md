# IGNA Studio Release Security Remediation - 2026 Q3

Remediation date: 2026-07-26

Starting candidate:
`90301e73b646a23227c129dadeaf9aea3c6f2bc5`

Remediation branch:
`fix/release-security-hardening-2026q3`

Baseline:
`origin/main` at `6b49f5c`

Verdict: **RELEASE READY WITH EXPLICITLY ACCEPTED RISKS**

No push, merge, deployment, production access, production mutation, or `.env`
modification was performed.

## Scope And Root Causes

The second-gate work addressed four confirmed release blockers:

1. Authentication state had no server-validated revocation generation. Role
   middleware did not check `is_active`, and account updates did not invalidate
   remembered authentication.
2. Locked production dependencies contained reachable Laravel and Dompdf
   advisories plus related transitive advisories.
3. Blog anchor sanitization relied on regular-expression transformations that
   did not safely normalize every quoted, unquoted, or obfuscated URL scheme.
4. The deployment script appeared to be an approved mutating deployment
   mechanism but lacked the release identity, backup, topology, and fail-closed
   gates required by the split Hostinger layout.

## Session-Revocation Architecture

The `users` table now has an unsigned `auth_session_version` value with a default
of `1`. Login records the current version in the session. Global web middleware
checks both the current database `is_active` state and the session version on
every authenticated request.

Deactivation and relevant credential-security updates increment
`auth_session_version` and rotate `remember_token` in one database transaction.
Administrative password reset uses the same revocation primitive. A stale or
inactive request is logged out, the session is invalidated, the CSRF token is
regenerated, and a localized message is returned. JSON callers receive 401.

This design does not depend on a queryable database-session table and therefore
works with the configured session abstraction. The acting administrator remains
authenticated when changing another user's credentials. Resetting one's own
credentials intentionally refreshes the current session version after the
security change.

Reactivation does not revive an old session or remember cookie. The user must
authenticate again with current credentials.

## Migration Impact

Migration:
`2026_07_26_000100_add_auth_session_version_to_users.php`

- Additive unsigned `BIGINT`, not null, default `1`.
- Existing users receive a safe version value.
- SQLite apply, rollback, reapply, and application tests passed.
- The schema operation is compatible with MySQL.
- The `down` method removes only the added column.
- Production must use a maintenance window and verified backup.
- Altering `users` may wait on a MySQL metadata lock; a lock timeout or
  unexpected long-running transaction is a stop condition.
- Sessions created before deployment have no version marker and will require a
  fresh login after the release.

## Dependency Remediation

Targeted Composer updates were used with dependency closure and minimal changes.
No unconstrained `composer update` was run.

### Starting Advisory Inventory

The candidate lock was re-audited in an isolated temporary directory. Only the
reviewed advisory fields are recorded here.

| Package | Advisory | Severity | Fixed boundary | Production reachability | Blocking |
| --- | --- | --- | --- | --- | --- |
| `laravel/framework` | `PKSA-m5cs-t1y6-qpcs` | Medium | `13.12.0` | Signed URL generation is used | Yes |
| `laravel/framework` | `PKSA-3r5d-mb8f-1qw9` | High | `13.10.0` | Public email validation and mail flows are used | Yes |
| `laravel/framework` | `PKSA-mdq4-51ck-6kdq` | Unknown | `13.10.0` | Same reachable email-validation surface | Yes |
| `dompdf/dompdf` | `PKSA-cv56-2228-pzr6` | Medium | `3.1.6` | Proposal PDF generation is used; SVG input is constrained | Yes |
| `dompdf/dompdf` | `PKSA-6r8f-nxsb-67bq` | Medium | `3.1.6` | Proposal PDF generation is used; bitmap input is constrained | Yes |
| `dompdf/dompdf` | `PKSA-gh7h-hhy4-byg7` | Medium | `3.1.6` | Proposal PDF generation is used; bitmap input is constrained | Yes |
| `dompdf/dompdf` | `PKSA-mwt3-h9tv-kx78` | Medium | `3.1.6` | Proposal PDF generation is used; SVG data URIs are constrained | Yes |
| `dompdf/dompdf` | `PKSA-hp6n-n4kz-21wk` | Low | `3.1.6` | Proposal PDF generation is used; font declarations are constrained | Yes, paired PDF gate |
| `dompdf/dompdf` | `PKSA-mckv-s5hg-868k` | Low | `3.1.6` | Proposal PDF generation is used; file paths are application-owned | Yes, paired PDF gate |
| `guzzlehttp/guzzle` | `PKSA-fy2t-3c5f-827y` | Medium | `7.15.1` | Transitive Google API client; integration is configuration-gated | No independent blocker |
| `guzzlehttp/guzzle` | `PKSA-qxvb-2bpp-dnk6` | Medium | `7.15.1` | Transitive Google API cookie handling; no direct application cookie jar | No independent blocker |
| `guzzlehttp/guzzle` | `PKSA-bbs6-q5q9-f3t4` | Medium | `7.15.1` | Transitive Google API client; integration is configuration-gated | No independent blocker |
| `guzzlehttp/guzzle` | `PKSA-bcdd-5xc7-gwfb` | Medium | `7.12.3` | Transitive Google API client; no reviewed IP-domain cookie flow | No independent blocker |
| `guzzlehttp/guzzle` | `PKSA-pwsk-hy21-4gby` | Medium | `7.14.2` | Transitive Google API client; no reviewed proxy-auth flow | No independent blocker |
| `guzzlehttp/guzzle` | `PKSA-93qv-9n9h-6k6p` | Medium | `7.12.1` | Transitive Google API cookie handling; no direct application cookie jar | No independent blocker |
| `guzzlehttp/guzzle` | `PKSA-k22t-f949-t9g6` | Medium | `7.12.1` | Transitive Google API client; no reviewed HTTPS proxy flow | No independent blocker |
| `guzzlehttp/psr7` | `PKSA-vznr-tgp9-fd7d` | Medium | `2.12.3` | Transitive HTTP URI serialization | No independent blocker |
| `guzzlehttp/psr7` | `PKSA-7qs6-zvnz-h66r` | Medium | `2.12.1` | Transitive HTTP start-line serialization | No independent blocker |
| `guzzlehttp/psr7` | `PKSA-gm5x-j3mz-71n9` | Medium | `2.10.2` | Transitive HTTP URI serialization | No independent blocker |
| `guzzlehttp/psr7` | `PKSA-jj5t-2zs1-dcfm` | Medium | `2.10.2` | Transitive HTTP authority serialization | No independent blocker |
| `phpseclib/phpseclib` | `PKSA-432p-hv1d-chf7` | Medium | `3.0.54` | Transitive X.509 code; no attacker-controlled certificate path found | No independent blocker |
| `symfony/http-foundation` | `PKSA-y6py-qpv1-h52p` | Medium | `8.0.13` | Framework is reachable; affected private-network client primitive is not used | No independent blocker |
| `symfony/polyfill-intl-idn` | `PKSA-dwsq-ppd2-mb1x` | Low | `1.38.1` | Transitive IDN normalization | No independent blocker |
| `symfony/routing` | `PKSA-bf7t-jnpz-492k` | Medium | `8.0.13` | URL generation is used; attacker-controlled chained dot segments were not found | No independent blocker |

All listed advisories are absent from the remediated lock. Packages marked as no
independent blocker were still updated as the smallest compatible dependency
closure, avoiding a partially advisory-bearing production lock.

| Package | Before | After | Disposition |
| --- | --- | --- | --- |
| `laravel/framework` | `13.6.0` | `13.12.0` | Reachable mail-header and framework advisories cleared |
| `dompdf/dompdf` | `3.1.5` | `3.1.6` | Reachable PDF advisories cleared |
| `guzzlehttp/guzzle` | `7.10.0` | `7.15.1` | Transitive production advisories cleared |
| `guzzlehttp/promises` | `2.3.0` | `2.5.1` | Compatible Guzzle closure |
| `guzzlehttp/psr7` | `2.9.0` | `2.13.0` | Compatible Guzzle closure |
| `phpseclib/phpseclib` | `3.0.52` | `3.0.54` | Production advisory cleared |
| `symfony/http-foundation` | `8.0.8` | `8.0.13` | Framework advisory cleared |
| `symfony/routing` | `8.0.12` | `8.0.13` | Framework closure |
| `symfony/polyfill-intl-idn` | `1.37.0` | `1.38.1` | Framework closure |
| `symfony/polyfill-php86` | absent | `1.38.0` | Required compatibility polyfill |

The root PHP requirement is now `^8.4`, matching the locked Symfony 8 platform
requirement. Local verification used PHP 8.5.8. Hostinger PHP 8.4 or newer is a
mandatory preflight condition.

`composer validate` passes. `composer audit --locked` reports zero advisories and
zero abandoned packages. Laravel boot, mail rendering, signed URLs, migrations,
ticket authorization, proposal PDF generation, long PDF output, and proposal
calculations pass in the application suite.

## Blog Sanitizer Correction

Blog sanitization now parses HTML through `DOMDocument` and enforces explicit tag
and attribute allowlists. Anchor URLs are decoded iteratively and normalized for
entities, casing, Unicode separators, whitespace, and control characters before
scheme validation.

Only HTTPS links and approved relative/hash links survive. `javascript:`,
`vbscript:`, `data:`, `file:`, protocol-relative URLs, unknown schemes, event
attributes, and executable embedded elements are removed. External HTTPS links
receive `nofollow noopener noreferrer`.

Sanitization runs before storage and again at public rendering, which protects
historical or directly inserted content without weakening the separate proposal
rich-text sanitizer.

## Deployment-Script Disposition

`scripts/deploy-hostinger.sh` is now a non-mutating, fail-closed preflight only.
It uses strict shell mode and verifies:

- exact application and public-bridge roots;
- exact production account and current directory;
- clean production `main`;
- non-empty full expected commit;
- commit availability and exact `origin/main` identity;
- fast-forward ancestry;
- explicit backup confirmation;
- private backup location, record, artifacts, and checksums;
- bridge entrypoint, build manifest, storage link, and parent-root refusal.

The script contains no fetch, pull, reset, install, migration, cache, copy,
delete, or synchronization operation. Local tests verify syntax, root, bridge,
branch, dirty-tree, expected-commit, backup, and non-mutation behavior.

The reviewed manual release runbook is the only approved mutation procedure for
this release. It uses `composer install`, verified backups, an exact
fast-forward-only commit, targeted bridge build replacement, migration stop
conditions, deployment records, smoke tests, and a separate rollback runbook.
The script was not executed against production.

## Medium-Finding Decisions

### Proposal Public Tokens - Remediated Now And Accepted Residual Risk

Public token and signed proposal routes now require a public status. `sent` and
`approved` proposals remain accessible; `draft`, `rejected`, and other
non-public statuses return 404 even when a historical token exists.

Token expiry and explicit revocation are not implemented in this release. They
remain an accepted risk requiring a future transition design so valid customer
links are not broken without notice.

### PDF Upload Security - Accepted Risk For This Release

Ticket PDFs remain private, authorization-gated, size/type validated, and served
as attachments. No malware scanner or active-content neutralizer is present.
Shared Hostinger constraints make a reliable local scanning daemon unsuitable
without a separately operated service. Do not claim scanning exists.

A future remediation should evaluate a managed scanning service, quarantine
workflow, retry/failure policy, privacy terms, retention, and a fail-closed
download decision.

### Response Security Headers - Remediated Now With Accepted CSP Follow-Up

Application middleware now emits:

- `X-Content-Type-Options: nosniff`;
- `X-Frame-Options: SAMEORIGIN`;
- `Referrer-Policy: strict-origin-when-cross-origin`;
- a restrictive `Permissions-Policy`.

A strict Content Security Policy is not included because the current templates
use inline and external resources that require an inventory and compatibility
rollout. CSP is a separate hardening task, not an undocumented current control.

## Local Diagnostic Disclosure

A prior private local tool transcript reportedly contained local
environment/session material. Those values were not reproduced, inspected, or
included in this remediation.

- Rotate the local development key and invalidate local sessions as a human
  cleanup action.
- Rotate production credentials only when evidence shows production values were
  exposed or reused.
- Rotating production `APP_KEY` invalidates encrypted cookies and may make
  encrypted application data unreadable unless a deliberate key-rotation plan
  exists.

Safe local cleanup:

1. Stop local Laravel workers and servers.
2. Clear site data for the local development origin in the browser.
3. In the local environment only, generate a replacement application key through
   the normal Laravel command and then clear Laravel caches.
4. Remove or invalidate local development sessions through the configured local
   session store.
5. Confirm no local key was copied into staging or production before considering
   any non-local rotation.

No `.env` file or production key was modified in this execution.

## Verification Record

- Focused remediation suite: 14 tests, 88 assertions.
- Broader security/workflow suites: 56 tests, 437 assertions.
- Full Laravel suite: 101 tests, 872 assertions.
- New migration apply, rollback, and reapply: passed.
- Composer validation and locked audit: passed, zero advisories.
- Production npm audit: passed, zero vulnerabilities.
- Full development npm audit: incomplete. The registry returned malformed
  compressed JSON on the command and one retry; `npm ping` succeeded. TLS and
  registry configuration were not weakened or permanently changed.
- Frontend production build: passed with Vite 8.
- Deployment script syntax and fail-closed tests: passed.
- `git diff --check`: passed.

## Browser Validation

Ignored local evidence records the following disposable-data checks:

- homepage, login, public request, tracking, and static assets;
- active client login and ticket/document authorization;
- deactivation followed by denial on the next request with the existing cookie;
- reactivation without stale-session restoration, followed by successful fresh
  login;
- administrative login and password reset while the acting administrator
  remained authenticated;
- ticket, file, and proposal administration;
- safe blog HTTPS rendering and removal of a direct-database unsafe anchor;
- public sent proposal access and 404 after changing the same proposal to draft;
- proposal PDF generation and visual inspection;
- no observed browser console errors, redirect loop, or HTTP 500.

Disposable QA records were removed. Evidence remains under ignored `output/`
paths and is excluded from the remediation commit.

`P2-RT-01` remains a known accepted non-blocking proposal rich-text toolbar
interaction defect. It was recorded separately and not redesigned in this pass.

## Remaining Findings

### CRITICAL

None.

### HIGH

None in the locally verified source candidate.

### MEDIUM

1. Hostinger read-only preflight and backup/restore authority remain a mandatory
   human gate.
2. Proposal tokens do not yet expire or support explicit revocation.
3. Uploaded PDFs do not have malware scanning or active-content neutralization.
4. A strict CSP remains pending compatibility work.
5. Full development-only npm audit status is incomplete due to registry/tool
   response failure.
6. Human local development key/session cleanup remains recommended.

### LOW

1. External Google Fonts remain an availability/privacy/CSP consideration.
2. Node reports a non-blocking module-registration deprecation warning during
   the successful build.

### INFORMATIONAL

1. PHP 8.4 or newer is required by the reviewed lock file.
2. Pre-release authenticated sessions intentionally require fresh login after
   the migration.
3. No production package is abandoned according to Composer.

## Commit And Release Boundary

The authorized local remediation commit is created only after the final gate
passes. Its hash is recorded in the final execution report because a commit
cannot reliably contain its own identity.

Local screenshots, PDFs, browser state, Graphify output, environment files,
private data, cookies, tokens, and signed URLs are excluded.

## Hostinger Preflight Still Required

Before any deployment approval, a human must verify through read-only SSH:

- exact production application and bridge roots;
- clean `main` at the exact approved release commit;
- PHP 8.4 or newer and required extensions;
- reviewed database driver and migration state;
- writable `storage` and `bootstrap/cache`;
- private backup location, backup checksums, and restore authority;
- public bridge entrypoint, build manifest, storage link, and absence of private
  Laravel directories;
- release artifact checksum and rollback owner.

Any mismatch is a stop condition. This report does not authorize deployment.

## Final Verdict

**RELEASE READY WITH EXPLICITLY ACCEPTED RISKS**

The source candidate is ready for human review and the mandatory Hostinger
preflight. It is not deployed and must not be treated as production-approved
until that separate gate succeeds.
