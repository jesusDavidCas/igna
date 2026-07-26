# IGNA Studio 2026 Q3 Release-Candidate Audit

Audit date: 2026-07-26

Repository: `/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform`

Candidate branch: `feat/proposal-admin-information-architecture`

Candidate commit: `90301e73b646a23227c129dadeaf9aea3c6f2bc5` (`90301e7`)

Base: `origin/main` at `6b49f5c`

First-gate verdict: **RELEASE BLOCKED**

Second-gate status: **RELEASE READY WITH EXPLICITLY ACCEPTED RISKS**

The first-gate findings below are preserved as historical evidence for candidate
`90301e7`. The current disposition is recorded in
`docs/audits/RELEASE_SECURITY_REMEDIATION_2026Q3.md` and in the second-gate
addendum at the end of this report.

This audit was read-only except for this documentation and ignored local QA
evidence. No push, merge, pull, rebase, tag, deployment, production mutation,
dependency update, migration, `.env` edit, staging, or commit was performed.

## Executive Summary

The Phase 1 and Phase 2 implementation is coherent, linear, and strongly tested.
The complete Laravel suite passes with 87 tests and 781 assertions. The focused
release suites pass with 86 tests and 780 assertions. The production frontend
build succeeds. Ticket ownership, stage sequencing, rollback, file visibility,
signed tracking, upload quarantine, document review, proposal validation,
restricted rich text, and proposal PDF behavior all have focused coverage.

The release is nevertheless blocked:

1. The locked Laravel framework (`13.6.0`) has a high-severity CRLF injection
   advisory in the default email rule. The application accepts unauthenticated
   user-supplied email addresses and sends email through public request and
   password-reset flows, so the affected surface is reachable.
2. Deactivation and administrative password reset do not revoke existing
   sessions or remember cookies. `EnsureUserRole` validates role only, so an
   inactive former administrator or client can retain access.
3. `scripts/deploy-hostinger.sh` is not safe for this split-root production
   architecture. It lacks backups, branch and cleanliness gates, exact-commit
   verification, maintenance/failure handling, public bridge synchronization,
   smoke tests, and rollback.
4. Passwordless Hostinger SSH was unavailable. The production commit, branch,
   cleanliness, PHP extensions, database driver/migration state, writable paths,
   Node capability, and public bridge state remain unverified.

The current candidate must not be merged to `main` or deployed until the blockers
in this report are remediated and the full gate is rerun.

## Release Identity And Branch Graph

Read-only `git fetch --prune origin` completed successfully. Remote refs did not
move during the audit.

```text
bee63f1  local main
   \
    6b49f5c  origin/main, origin/HEAD, ui/landing-distinctive-refresh
       |
       1824b89  Phase 1, release/igna-workflow-proposals-2026q3,
                 fix/ticket-workflow-integrity
       |
       90301e7  Phase 2, feat/proposal-admin-information-architecture (HEAD)
```

- `1824b89` is an ancestor of the candidate.
- `90301e7` is the candidate HEAD.
- The release branch contains Phase 1 but not Phase 2.
- `origin/main...HEAD` is `0 behind / 2 ahead`.
- `origin/main...release/igna-workflow-proposals-2026q3` is `0 behind / 1 ahead`.
- The release branch can be fast-forwarded to `90301e7`.
- The two release commits each have one parent; candidate ancestry is linear.
- Candidate diff: 64 files, 6,001 insertions, 544 deletions.

## Worktree Inventory

Tracked files were clean at the start and after executable verification.
`git diff --check` passed. No conflict markers were found.

| Path | Classification | Release action |
| --- | --- | --- |
| `.agents/`, `.graphifyignore`, `AGENTS.md`, `skills-lock.json` | Graphify/agent tooling baseline | Exclude |
| `.playwright-cli/`, `output/` | Local browser/PDF evidence | Exclude |
| `graphify-out/`, `graphify-query-smoke-test.txt` | Graphify generated output | Exclude |
| `docs/AI_ARCHITECTURE_GRAPH.md` | Graphify architecture baseline | Exclude unless separately reviewed |
| `docs/LANDING_UI_SYSTEM.md`, `resources/images/` | Pre-existing landing work | Exclude from this release plan |
| `docs/audits/` existing content | Pre-existing audit baseline | Preserve; include only the three new Q3 documents intentionally |
| `tests/Feature/FunctionalBoundaryTest.php` | Local-only boundary test baseline | Do not include without a separate commit decision |
| `.env` and `.env.backup-*` | Ignored sensitive local artifacts | Exclude; contents were not intentionally inspected |

No local-only evidence, Graphify output, screenshots, QA PDFs, or environment
artifact may enter a release commit.

## Test, Build, And Runtime Baseline

| Check | Result |
| --- | --- |
| Laravel | 13.6.0 |
| PHP | 8.5.8 |
| Composer | 2.10.2 |
| Node | 26.5.0 |
| npm | 11.17.0 |
| Local database | SQLite |
| Local mail | Log |
| Local queue | Sync |
| Full test suite | 87 passed, 781 assertions, 12.32 s |
| Focused release suites | 86 passed, 780 assertions, 11.14 s |
| Production frontend build | Passed in 867 ms |
| Build output | CSS 95.72 kB; JS 0.32 kB; Vite manifest generated |
| Build warning | Node `module.register()` deprecation |
| `git diff --check` | Passed |
| Local migrations | All ran, including the two Phase 1 migrations |

The first non-debug build process became idle and was terminated. A retry
completed normally. This is classified as an environment-specific build-process
stall, not a release regression.

Focused coverage includes:

- login redirects, throttling, logout/session invalidation, and password reset;
- admin and super-admin boundaries;
- tracking email binding and malformed-input rejection;
- ticket stage completion, duplicate protection, sequential rollback, and audit;
- cross-ticket and cross-client file denials;
- public, tracking, and authenticated client uploads;
- upload MIME/extension/signature/dimension checks and private quarantine;
- document download/review/reject lifecycle;
- proposal create/update, template behavior, validation, sorting, public view,
  rich-text sanitization, and PDF rendering.

## Secret And Backdoor Review

`gitleaks` was not installed, so no new scanner was installed. A redacting
fallback scanned tracked current source and Git patch history while excluding
`.env`, `.env.*`, and private-key file types. It searched for private-key blocks,
GitHub tokens, AWS access keys, bearer tokens, and hard-coded credential
assignments. It emitted no findings.

Tracked sensitive-file inventory contains only `.env.example`. `.env` and the
known local `.env.backup-*` pattern are ignored and must remain outside release
content.

No tracked occurrence was found for dynamic `eval`, `shell_exec`, `exec`,
`system`, `passthru`, `proc_open`, `popen`, `unserialize`, executable
`base64_decode`, TLS verification disablement, or `chmod 0777`.

Persistence review found:

- no application scheduler registrations;
- no queued `ShouldQueue` application jobs;
- no model observers or hidden event listeners;
- only the default local `inspire` Artisan command;
- Google Drive API calls are explicit and configuration-gated;
- Graphify/Codex hooks and output are development tooling, not production
  application behavior.

Audit-process incident: a Vite debug retry and one local header diagnostic
emitted local environment and session material into the private tool transcript.
No value is included in this file, any runbook, Git, or the final report.
Invalidate local sessions and rotate the local application key. If the local key
was ever reused outside this workstation, rotate it there through the relevant
secret manager before release.

## Authentication And Authorization

Verified controls:

- login is throttled to five attempts per minute;
- password-reset requests are throttled and return a generic response;
- successful login regenerates the session;
- logout invalidates the session and regenerates CSRF state;
- password reset rotates the remember token;
- admin routes require authentication plus admin/super-admin role;
- user and settings management require super-admin;
- the last active super-admin guard is implemented and tested;
- client ticket views and uploads check ticket ownership;
- all ticket file actions bind the file back to the ticket;
- proposal administration is admin-only;
- CSRF protection is inherited from Laravel's web middleware.

Blocking gap:

- `AuthenticatedSessionController` rejects inactive users only immediately after
  password login.
- `EnsureUserRole` checks authentication and role but not `is_active`.
- Administrative deactivation and administrative password reset do not
  invalidate existing sessions or rotate `remember_token`.
- An inactive account with an existing session or remember cookie can therefore
  continue to satisfy admin/client route middleware.

Required remediation:

1. Enforce `is_active` on every authenticated request.
2. Rotate `remember_token` and invalidate/revoke sessions on deactivation,
   role removal, and administrative password reset.
3. Add tests proving an already-authenticated or remembered inactive admin and
   client are denied immediately.

## Public Links

Signed tracking and credential routes combine signed middleware, resource
binding, email-context hashes where applicable, and throttling. Tracking upload
links expire after 30 minutes. Tests cover bad signatures, wrong context, and
cross-ticket manipulation.

Proposal token links use 40 random characters and a unique database column, which
is adequate against enumeration. However, proposal token links:

- have no expiry;
- have no explicit revocation timestamp or rotation action;
- do not restrict draft, rejected, or superseded proposal status;
- remain replayable until the token is manually changed in data.

This is a medium privacy/lifecycle finding. It is not the current release blocker
but requires explicit acceptance or a follow-up remediation.

The Laravel temporary signed URL advisory is specific to local-filesystem
temporary URLs. This application uses signed routes rather than
`Storage::temporaryUrl()` for the reviewed ticket/proposal flows, so direct
reachability was not established. Upgrade is still required because the same
framework update also addresses the reachable mail advisory.

## Upload And File Security

Client and public ticket documents are limited to 2 MB PDF/JPEG/PNG, require
extension/MIME agreement, reject double extensions and null bytes, validate PDF
header/EOF, enforce image dimensions/pixel limits, use UUID storage names, and
store under the private local disk in a pending-review quarantine state. Images
are re-encoded when GD is available. Downloads use application authorization,
attachment disposition, and `X-Content-Type-Options: nosniff`.

Administrator ticket uploads permit business formats up to 20 MB, including
ZIP/DWG/DXF, but are admin-only and remain behind application download routes.
Public branding/blog/team/signature images are constrained to image extensions.
SVG and HTML are not accepted by reviewed upload rules.

Residual risk:

- PDF validation is structural, not malware scanning or active-content removal.
- If GD is unavailable, valid JPEG/PNG client files are stored without
  re-encoding, though still private and served as attachments.
- Google Drive availability and sharing defaults must be verified on production
  before enabling that integration.

These are medium/low hardening items because quarantine, authorization, and
attachment delivery materially reduce reachability.

## XSS And Rich Content

Proposal description/scope input is cleaned before persistence and cleaned again
before raw Blade/PDF rendering. Allowed tags are limited to paragraphs, breaks,
strong/emphasis, and lists; attributes, anchors, images, SVG, scripts, forms,
iframes, and active media are stripped. Focused sanitizer tests pass.

The admin-managed blog sanitizer has an incomplete anchor protocol defense:
quoted `javascript:` values are replaced, but unquoted `javascript:` and other
active URI schemes can survive on an allowed `<a>` tag. This is stored XSS
requiring an authenticated administrator to supply content, but it can target
public readers or another administrator. Classify as **MEDIUM** and replace the
regex-only anchor handling with parsed allowlisted `http`, `https`, `mailto`, and
relative URLs plus focused bypass tests.

No unsanitized proposal content was found in public HTML or PDF rendering.

## Dependency And Supply-Chain Review

`composer audit --locked` reported 24 advisories affecting eight production
packages. No package was updated during this audit.

| Package | Locked | Advisories | Direct | Exposure and decision |
| --- | --- | ---: | --- | --- |
| `laravel/framework` | 13.6.0 | 3 | Yes | High mail CRLF advisory is reachable; **BLOCKER**. Upgrade to at least 13.12.0. |
| `dompdf/dompdf` | 3.1.5 | 6 | Yes | Proposal PDF is reachable, but user rich text cannot inject images/SVG. Upgrade to 3.1.6; defense-in-depth blocker paired with dependency remediation. |
| `guzzlehttp/guzzle` | 7.10.0 | 7 | No | Google API transitive surface; Drive is configuration-gated. Upgrade to 7.15.1 or later. |
| `guzzlehttp/psr7` | 2.9.0 | 4 | No | Transitive HTTP serialization surface. Upgrade to 2.12.3 or later. |
| `phpseclib/phpseclib` | 3.0.52 | 1 | No | Transitive Google/X.509 surface; no reviewed attacker-controlled certificate validation. Upgrade above 3.0.53. |
| `symfony/http-foundation` | 8.0.8 | 1 | No | SSRF-bypass primitive not found in application use. Upgrade to 8.0.13 or later. |
| `symfony/polyfill-intl-idn` | 1.37.0 | 1 | No | Low-severity IDN equivalence issue. Upgrade to 1.38.1 or later. |
| `symfony/routing` | 8.0.12 | 1 | No | Dot-segment URL generation issue; no attacker-controlled route path generation proven. Upgrade to 8.0.13 or later. |

Primary advisory references:

- Laravel mail CRLF: https://github.com/advisories/GHSA-5vg9-5847-vvmq
- Laravel temporary signed URL path confusion:
  https://github.com/advisories/GHSA-crmm-hgp2-wgrp
- Dompdf embedded SVG information leak:
  https://github.com/advisories/GHSA-j8qw-6jw8-r297
- Dompdf SVG data-URI local read:
  https://github.com/advisories/GHSA-cx96-42px-69fm

`npm audit --omit=dev` reported zero production vulnerabilities. The full npm
audit failed twice because the registry audit endpoint returned malformed
compressed JSON. Development-only npm advisory status is therefore unverified.
Retry it before integration. There is no tracked Dependabot configuration.

Composer install hooks are standard Laravel discovery hooks. Allowed Composer
plugins are limited to Pest and PHP HTTP discovery. The package lock files were
not changed.

Required remediation must occur on a dedicated dependency-security branch using
reviewed lock-file updates, followed by Composer audit, full tests, PDF render,
browser QA, and production compatibility checks.

## Migration Review

Only two migrations differ from `origin/main`:

| Migration | Impact | Compatibility | Risk |
| --- | --- | --- | --- |
| `2026_07_25_000100_add_client_document_metadata_to_ticket_files.php` | Adds upload source, review status, context hash, reviewer FK, and review time. Existing rows default to `admin` / `reviewed`. | Ran on SQLite; uses Laravel schema types compatible with MySQL. | Low/medium table-alter lock; additive. |
| `2026_07_25_000200_add_review_download_and_stage_rollback_metadata.php` | Adds download/rejection metadata, stage-attempt/supersession metadata, and new audit table. Existing attempts default to 1. | Ran on SQLite; FKs and types are MySQL-compatible. | Medium: alters two active tables and creates FK-heavy audit table. |

The migrations:

- do not delete tickets, proposals, ticket files, or stage events;
- do not change existing ticket/proposal statuses;
- do not depend on QA seed data;
- preserve existing files and classify existing files as reviewed admin uploads;
- are reversible at schema level, but rollback discards new review/audit metadata;
- may acquire MySQL metadata/table locks proportional to production table size.

A database backup is mandatory. Run in filename order. Do not use broad
`migrate:rollback` in production. A targeted reverse-order procedure is in the
rollback runbook, and database restore is preferred after partial DDL failure or
after new production audit/review data has been created.

## Deployment Script And Hosting Architecture

The documented split-root architecture is correct:

- private Laravel root:
  `/home/u935649387/apps/igna-studio`
- public bridge:
  `/home/u935649387/domains/ignastudio.com/public_html/igna-app`

Only the bridge entry files, build assets, explicitly public static assets, and
the reviewed public-storage link may be exposed. `.env`, source, routes, config,
database, storage internals, vendor, tests, Git, agent, and Graphify files must
never enter `public_html`.

`scripts/deploy-hostinger.sh` is **not approved for use**. It:

- pulls `origin main` into whichever branch is currently checked out;
- does not require a clean repository or `main`;
- does not verify the expected release commit;
- makes no code/database/storage/public backup;
- has no maintenance-mode or failure trap;
- migrates without a rollback checkpoint;
- runs `storage:link` only in the private app public directory;
- never synchronizes the actual public bridge;
- has no public-entry integrity check;
- has no smoke tests, log check, or rollback;
- does not record the previous production commit.

Existing legacy deployment docs also contain unsafe broad `rm -rf`, non-ff-only
pulls, placeholders, and direct-to-main practices. The new Q3 runbooks supersede
them for this release.

## Hostinger Preflight

Passwordless read-only SSH failed with `Permission denied (publickey,password)`.
No password was requested and no server command ran.

Unverified production facts:

- current commit, branch, and cleanliness;
- database driver and migration state;
- PHP/Composer/Node/npm versions;
- required PHP extensions (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`,
  `intl`, `zip`);
- writable `storage` and `bootstrap/cache`;
- public bridge ownership, entrypoint, build manifest, and storage link;
- production `APP_ENV`, `APP_DEBUG`, HTTPS/secure-cookie/proxy/host settings.

This unverified preflight is a deployment stop condition. Exact human read-only
commands are in the deployment runbook.

## Local HTTP, Browser, And PDF QA

Local HTTP checks returned 200 for `/`, `/login`, `/tracking`, `/up`,
`/robots.txt`, `/sitemap.xml`, and a QA public proposal without printing its
token.

The local response did not expose application-level CSP, frame-ancestor/X-Frame,
referrer-policy, permissions-policy, or explicit MIME-sniffing headers. Production
may add them at Apache/CDN level; this must be verified. `X-Powered-By` should be
disabled in production.

The in-app browser could not revisit localhost after its initial connection-error
page because the browser URL policy blocked the target. Browser rules prohibited
an indirect bypass. New interactive release-candidate browser evidence is
therefore environment-blocked.

Existing current-commit Phase 2C evidence remains under ignored
`output/ui-review/phase-2c/`: 11 screenshots at 390, 768, 1024, and 1440 widths,
with zero recorded failures and zero console errors. It covers proposal create,
edit, validation retention, save/reload, admin show, public proposal, PDF route,
and toolbar commands.

The current proposal PDF evidence:

- Dompdf 3.1.5, PDF 1.7;
- A4 landscape, one page for the QA fixture;
- no JavaScript, forms, or encryption;
- visually legible with no clipping, overlap, raw tags, or broken glyphs;
- bold, italic, bulleted, and numbered rich text rendered;
- totals and signature layout remained intact.

## Known Deferred Defect: P2-RT-01

The human reports that Bold, Italic, Bulleted list, Numbered list, and Clear
formatting may still fail interactively. Current browser automation could not
independently reproduce or clear the report because localhost navigation was
policy-blocked.

Static review confirms the editor still depends on deprecated
`document.execCommand`. Server-side sanitizer, validation retention,
create/update persistence, public rendering, and PDF rendering tests pass.
Existing Phase 2C evidence shows successful toolbar actions, but that does not
override the human report.

Classification: **known accepted UX defect requiring later repair**.

It is not a security blocker because stored/public/PDF content is sanitized. It
is not presently a data-loss blocker because proposal submission and persistence
pass tests. Human acceptance is required before release after all security and
deployment blockers are resolved.

## Findings By Severity

### CRITICAL

None confirmed.

### HIGH

1. **RC-H-01 - Reachable vulnerable Laravel mail validation**
   - Locked Laravel 13.6.0 is affected by GHSA-5vg9-5847-vvmq.
   - Public request and reset flows accept unauthenticated user email and send
     mail.
   - Remediation: update the lock file to Laravel 13.12.0 or later on a dedicated
     branch and rerun all gates.
2. **RC-H-02 - Inactive/password-reset accounts retain authenticated access**
   - Active state is checked only after password login; role middleware omits it.
   - Existing sessions/remember tokens survive deactivation and admin password
     reset.
   - Remediation: per-request active gate plus session/remember-token revocation
     and tests.
3. **RC-H-03 - Existing deployment script is unsafe for production**
   - Missing branch/clean/commit gates, backups, public bridge sync, controlled
     failure, smoke tests, and rollback.
   - Remediation: do not run it; use the reviewed Q3 runbook or implement and
     review a replacement script.
4. **RC-H-04 - Production compatibility and state unverified**
   - Passwordless SSH was unavailable.
   - Remediation: human runs the read-only preflight and records redacted results.

### MEDIUM

1. **RC-M-01 - Admin-managed blog anchor sanitizer bypass**
   - Unquoted/alternate active URI schemes can survive on allowed anchors.
2. **RC-M-02 - Proposal share tokens lack expiry, revocation, and status gate**
   - High entropy prevents enumeration but not replay after disclosure.
3. **RC-M-03 - PDF/image upload defense is not malware scanning**
   - Private quarantine and attachment delivery reduce exposure.
4. **RC-M-04 - Twenty-three additional locked Composer advisories**
   - Most reviewed surfaces are constrained or configuration-gated, but all
     should be cleared in the dependency remediation.
5. **RC-M-05 - Audit-process local secret/session output**
   - Rotate local key, invalidate local sessions, and confirm no key reuse.

### LOW

1. **RC-L-01 - Security headers not present in local application responses**
   - Verify/implement at Apache/CDN or application middleware.
2. **RC-L-02 - External Google Fonts stylesheet**
   - Availability/privacy/CSP consideration; pin or self-host if required.
3. **RC-L-03 - Full npm development audit unavailable**
   - Registry endpoint returned malformed JSON twice; production npm audit is
     clean.

### INFORMATIONAL

1. No tracked secret, private key, backdoor, dynamic execution, disabled TLS,
   hidden scheduler, or unexpected persistence mechanism was found.
2. Phase 1/2 history is linear and the release branch is fast-forwardable.
3. Local tests, migrations, HTTP routes, production build, and PDF evidence pass.

## Required Remediation And Human Decisions

Before another release-readiness verdict:

1. Human approves a dependency-security branch and lock-file update.
2. Engineering fixes inactive-session/remember-token revocation.
3. Engineering fixes the blog sanitizer bypass.
4. Human rotates the local application key, invalidates local sessions, and
   confirms whether the key was reused elsewhere.
5. Human runs the Hostinger read-only preflight and records non-secret results.
6. Human decides whether P2-RT-01 and proposal-token lifecycle are accepted risks.
7. The complete tests, build, Composer/npm audits, browser QA, PDF visual QA,
   migration review, and deployment rehearsal are rerun.
8. The existing deployment script remains unused unless separately corrected,
   tested, and approved.

## First-Gate Verdict (Historical)

**RELEASE BLOCKED**

Do not integrate to `main` and do not deploy. The deployment and rollback
runbooks are planning artifacts only until all high findings are closed.

## Second Release Gate - 2026-07-26

The release blockers identified above were remediated on
`fix/release-security-hardening-2026q3`, based on
`90301e73b646a23227c129dadeaf9aea3c6f2bc5`.

### Closed Blockers

1. **Session revocation:** every authenticated web request now validates the
   user's active state and an `auth_session_version` marker. Deactivation and
   credential-security changes rotate the remember token and session version.
   Stale sessions are logged out, invalidated, and redirected without a loop.
2. **Composer advisories:** targeted Laravel, Dompdf, Guzzle, phpseclib, and
   Symfony updates remove all locked Composer advisories. Production Composer
   packages contain no abandoned package.
3. **Blog stored XSS:** blog HTML now uses parsed DOM allowlisting and normalized
   URL-scheme validation at storage and render time. Unsafe quoted, unquoted,
   entity-obfuscated, mixed-case, and control-character schemes are removed.
4. **Deployment automation:** `scripts/deploy-hostinger.sh` is now a
   non-mutating, fail-closed production preflight. The reviewed manual runbook is
   the only approved release mutation procedure for 2026 Q3.

### Verification

- Focused remediation suite: 14 tests, 88 assertions.
- Full Laravel suite: 101 tests, 872 assertions.
- Composer validation: passed.
- Locked Composer audit: zero advisories, zero abandoned packages.
- Production npm audit: zero vulnerabilities.
- Full development npm audit: incomplete because the registry returned malformed
  compressed JSON twice; `npm ping` succeeded and no registry/TLS setting was
  changed.
- Frontend production build: passed.
- New migration: apply, rollback, reapply, and status verification passed.
- Deployment script: syntax and local fail-closed tests passed.
- Browser QA: public, admin, and client paths passed, including next-request
  deactivation, fresh-login-only reactivation, admin password reset, blog link
  sanitization, status-gated public proposals, and proposal PDF rendering.

### Explicitly Accepted Risks

- `P2-RT-01`, the proposal rich-text toolbar interaction defect, remains accepted
  and was not redesigned in this security pass.
- Proposal tokens remain high entropy but do not yet expire or support explicit
  revocation. Draft, rejected, and other non-public statuses now return 404 even
  when a historical token exists.
- Uploaded PDFs are private and authorization-gated, but malware scanning and
  active-content neutralization are not implemented on the current shared-hosting
  architecture.
- Baseline response headers are now emitted. A strict Content Security Policy
  remains a separate compatibility project because the application currently
  uses inline and external resources.
- Full development-only npm advisory status remains incomplete due to the
  registry/tool response failure. The production dependency audit is clean.

### Pending Human Gate

Hostinger SSH preflight was not performed. A human must verify the production
root and bridge, clean `main`, exact approved release commit, PHP 8.4 or newer,
extensions, database and migration state, writable paths, backup restore
authority, and bridge identity before any deployment approval.

## Second-Gate Verdict

**RELEASE READY WITH EXPLICITLY ACCEPTED RISKS**

Source remediation is locally verified. No push, merge, deployment, production
access, production mutation, or `.env` modification was performed.
