# Website Readiness Sprint 7B

Date: 2026-10-22

## Scope and baseline

- Isolated branch: `codex/phase-5b5-readiness-sprint-7b`
- Baseline: `75739efadcf32c77dd66f7171b1deddb039027a8`
- This is a local, unstaged implementation. It does not publish English
  content, run a production migration, change Hostinger, push, or deploy.
- The original Sprint 7 governance note remains unmodified in its separate
  worktree.

## Implemented publication governance

Two additive MariaDB/SQLite-compatible tables now provide the missing durable
editorial record:

| Table | Parent relationship | English fields | Publication control |
| --- | --- | --- | --- |
| `blog_post_translations` | restrictive FK to `blog_posts` | title, summary, sanitized body HTML, optional SEO title and description | status, source fingerprint, approved source fingerprint, stale timestamp, approver and approval timestamp |
| `team_member_translations` | restrictive FK to `team_members` | role, summary, biography, expertise, optional SEO title and description | same review fields |

Both tables use a unique parent-and-locale key and indexes covering locale,
status, and approval time. No Spanish field was rewritten and no `demo.*`
language-file value was copied into a translation record.

The migration deliberately uses restrictive parent foreign keys. Team members
are hard deleted only through the existing locked, audited deletion service,
which explicitly removes translations after the dependency review. There is no
database cascade capable of bypassing that control. Blog posts are soft deleted;
the existing published-parent query prevents a soft-deleted or unpublished
source from exposing an English page.

## Editorial and stale-source controls

- Administrators may save English content as draft or submit it for review.
  Request input has no accepted approval, approver, or approval-time field.
- Active Super Admins alone can approve. Approval locks the parent and
  translation rows in one transaction, verifies parent eligibility, complete
  English content, review status, and equality of the recorded and live Spanish
  fingerprints.
- Translated Blog HTML passes through the existing HTML sanitizer before it is
  stored.
- A material Spanish source edit marks the retained English translation stale.
  More importantly, public eligibility recalculates the current source
  fingerprint for every request, so a direct database update cannot expose an
  old translation.
- Editing an approved translation removes its approval and returns it to the
  draft/review flow. Reverting the Spanish source does not restore an old
  approval.

English publication remains disabled by default through
`igna.seo.english_publication.enabled=false`. When a future owner enables that
switch, Blog pages additionally require an explicit slug registry and an
approved current translation. Team pages require an approved current
translation. Eligible English pages alone receive reciprocal alternates and
sitemap entries.

## Migration and rollback rehearsal

The disposable SQLite test database covers fresh creation, parent FK
enforcement, unique parent/locale enforcement, preservation of existing Spanish
source data, and an empty-table down/up migration. It explicitly verifies all
populated rollback-refusal cases: Blog-only, Team-only, and both tables
populated. `down()` preflights both tables before issuing either DDL statement,
so a refusal leaves both tables intact and does not permit a partial drop.

An application-code rollback must leave the additive tables and editorial
records in place; dropping populated translations is not a deployment rollback
step. The local workstation has no running MariaDB server, so MariaDB DDL,
foreign-key, and lock behavior remains an external disposable-environment gate.
MariaDB's auto-committing DDL is the reason the preflight occurs before the
first `DROP TABLE`; a failing production schema rollback requires restoring the
verified database backup rather than attempting an improvised destructive retry.

## Synthetic lifecycle evidence

Automated coverage demonstrates:

1. Published Spanish source -> English submission for review.
2. Client-supplied approval fields are ignored.
3. An administrator cannot approve; an active Super Admin can.
4. Controlled test configuration makes the approved English route eligible.
5. A Spanish edit immediately makes it return 404.
6. Reverting Spanish does not restore the old approval.
7. A new submission and approval restores eligibility.

It also covers CSRF rejection, source changes that bypass model events,
Team-profile approval and stale invalidation, route alternates, sitemap
eligibility, restrictive Team foreign keys, and controlled deletion.

The focused governance suite completed with 47 assertions. The complete suite
now completes with 76 assertions, including authorization, status-transition,
approval-metadata, deletion, stale-source, and populated-rollback cases. The
complete suite completed with 1,938 assertions under the documented Spanish
test locale. It reported 223 inherited fixture-read warnings and no failures.
Composer validation, locked Composer audit, platform checks, production npm
audit, the Vite production build, route caching, candidate-only Pint, and the
release hygiene check passed. The isolated worktree has no `.env` or active
local database file, so ordinary `migrate:status` is intentionally unavailable;
the disposable migration rehearsal above is the database evidence for this
sprint.

Public eligibility now fails closed unless an approved translation also records
an approver, approval time, matching approved source fingerprint, complete
English primary fields, and an available source parent. A nonmaterial Blog
header-image change does not stale the translation. An unpublished or
soft-deleted Blog parent, or an inactive Team parent, cannot remain public.

## Content register and owner approvals

No English editorial content was invented or published. The owner must supply
and approve real English content before enabling publication.

| Surface | Required source package | Publication status |
| --- | --- | --- |
| `/en` homepage | approved English hero, services, project/process copy, form/CTA copy, all displayed Team and Blog excerpts | blocked: homepage has no persistent completeness/approval model |
| `/en/blog` | approved translations for each listed published Blog article | blocked: no owner-approved translation records |
| `/en/blog/{slug}` | source-specific English title, summary, sanitized body, optional SEO text, Super Admin approval | technically ready but disabled by default |
| `/en/team/{slug}` | English role, summary, biography, expertise, optional SEO text, Super Admin approval | technically ready but disabled by default |

The owner must separately decide whether the homepage receives an equivalent
durable approval model. This implementation intentionally does not make `/en`
eligible merely because interface translation strings exist.

## Remaining infrastructure and approval blockers

1. Phase 5B.5A anti-spam must remain outside this branch until Hostinger
   supplies written trusted-proxy/client-IP topology evidence. Request: exact
   reverse-proxy IP ranges, whether forwarded headers are stripped/replaced at
   the edge, which header contains the verified client IP, and confirmation of
   the application-facing peer address. Do not trust all proxies with `*`.
2. WEB-01, WEB-02, and unpublished legal drafts need verified business identity,
   privacy contact, processing/retention decisions, and legal approval.
3. WEB-05 is denied by default locally; production cookie attributes and any
   future vendor activation require human verification.
4. WEB-19 remains disabled: no approved measurement purpose, GA4 measurement
   ID, vendor authorization, or conversion contract was provided.
5. Core Web Vitals and Hostinger transport/cache evidence require repeatable
   production measurement. Existing local changes do not establish field INP,
   LCP, or CLS.

## Required release treatment

The migration is additive, but it is not authorized for production yet. A
future deployment must take verified database/application/build/public-bridge
backups, install locked dependencies only, apply this migration only after
owner approval, and never drop populated translation tables during rollback.
The baseline security release remains the rollback target for application code.
