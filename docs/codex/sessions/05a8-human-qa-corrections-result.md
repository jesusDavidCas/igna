# Phase 5A.8 Human QA Corrections Result

Date: 2026-07-30

Branch: `fix/phase-5a-human-qa-corrections`

## Checkpoint

Phase 5A.7 blockers were recorded in commit `7cae3fb9f2b14bf488bfed05925926b874ff4a8a` with message `docs: record phase 5a human qa blockers`. The corrective branch was created from that checkpoint. Phase 5A.8 changes remain unstaged for human review.

First Phase 5A.8 human acceptance result: HUMAN ACCEPTANCE FAILED — SECOND CORRECTIVE ROUND REQUIRED.

Round 4 verdict: NOT READY. The real credential upload/regeneration/protected-viewer path and rich-text toolbar behavior were accepted by human QA, and the normal proposal-template removal workflow now uses permanent deletion. The remaining blocker is automatic dynamic database translation: no real dynamic-content translation provider is configured, so fake-provider tests pass but real-provider acceptance cannot be claimed.

## Corrections Made

- Hardened PDF credential rasterization diagnostics and page discovery.
- Changed protected credential PDF serving from attachment to inline protected derivative.
- Added an inline PDF credential viewer with signed derivative route and iframe fallback.
- Replaced visible duplicate bilingual service fields with a single current-locale authoring field.
- Prevented fake dynamic translations from copying source text into target-locale caches.
- Preserved valid legacy translation caches when editing through the single-field forms.
- Simplified proposal-template catalogue cards and restored the missing localized edit label.
- Replaced duplicate template title inputs with one visible current-locale title field.
- Replaced duplicate proposal-template cost-row description controls with one visible current-locale row field and hidden translation-cache preservation.
- Added a local-only `content:translate-missing` command for provider-backed sync of missing or copied service/template translations.
- Added deterministic rich-text ordered and unordered list insertion.
- Synchronized rich-text visible-character counters with server-side validation.
- Added scoped browser and PDF CSS for rich-text list markers.
- Added nullable proposal title locale caches and localized proposal title rendering for admin, public, WhatsApp, and PDF surfaces.
- Extended the translation sync command to proposal titles and to fail closed on unrestricted non-dry-run mutation.
- Replaced proposal-template deactivate/reactivate controls with a permanent `DELETE` workflow.
- Kept legacy inactive templates visible and usable until a human intentionally deletes them.

## Tests

Focused tests:

- Credential protection.
- Public credential rendering.
- Service administration structured content.
- Grouped public service taxonomy.
- Proposal-template catalogue.
- Proposal administration usability and rich text.

Focused result: passing.

Second corrective round focused result:

- `ProposalCostTemplateCatalogueTest`: 10 tests, 98 assertions passing.
- `ServiceAdministrationStructuredContentTest`: 12 tests, 84 assertions passing.
- Focused blocker band across proposal templates, proposal administration, credential protection, and public platform: 36 tests, 360 assertions passing.

Round 4 focused result:

- `ProposalCostTemplateCatalogueTest`: 12 tests, 119 assertions passing.
- `ServiceAdministrationStructuredContentTest`: 13 tests, 96 assertions passing.
- `ProposalAdministrationUsabilityTest`: 10 tests, 141 assertions passing.
- Phase 5A regression band across credentials, ticket documents, ticket workflow, public service taxonomy, services, proposal templates, and proposal administration: 76 tests, 803 assertions passing.

Full regression result: `php artisan test` passed with 138 tests and 1172 assertions.

Round 4 full regression result: `php artisan test` passed with 144 tests and 1244 assertions.

Build and security:

- `composer validate`: passed.
- `composer audit --locked`: no advisories.
- `npm audit --omit=dev`: zero vulnerabilities.
- `npm audit`: development-only advisories remain.
- `npm run build`: passed with the known Node deprecation warning.
- `git diff --check`: clean.
- Additive proposal-title migration rehearsal against disposable SQLite passed apply, rollback one step, and reapply.
- Local `php artisan migrate --force` applied `2026_07_30_000300_add_localized_title_to_proposals`.

## Browser Review

Local browser review confirmed:

- Service edit page has no visible duplicate bilingual source fields.
- Proposal-template catalogue has clean card labels and no raw `site.edit` key.
- Proposal-template edit page has one visible title input.
- Proposal editor exposes bold, italic, unordered list, ordered list, and clear-format commands with `10,000` visible-character counters.
- A synthetic local protected credential renders through an inline PDF object/iframe using the protected derivative route.
- A missing-derivative credential still fails closed.
- The real local `Master.pdf` credential validation file uploaded through the admin team form, generated a protected derivative, opened in the protected viewer, and regenerated successfully without changing the original source hash.
- Proposal rich text generated semantic bullet and numbered list markup through the browser paste-selection path, survived save/reload, and rendered visible markers in the generated Dompdf PDF.

Evidence is under ignored `output/` review directories.

## Notes For Human QA

Repeat acceptance on:

- Real admin credential upload and regenerate flow, including any older local failed records the human still expects to regenerate.
- Public inline protected PDF viewer in the human target browser.
- Service edit in both locales after a real dynamic translation provider is configured.
- Proposal title switching and selected-locale PDF output after a real dynamic translation provider is configured.
- Proposal-template catalogue, edit pages, row descriptions, and permanent deletion in both locales after a real dynamic translation provider is configured.

Do not treat the absence of a dynamic translation provider as automatic translation. The system now preserves existing translations, rejects copied-source fake targets, exposes local sync tooling, and must remain NOT READY until a real provider-backed end-to-end translation passes.

## Round 6 Finalization

Human QA confirmed that all major localization surfaces were working except service deliverables. The reproduced service 13 (`PTP`) data had English deliverable rows with blank Spanish cache values; the Spanish catalog contained localized service parent content but no engineering deliverable list. Round 6 added catalog deliverable translations, backfilled persisted deliverable locale caches, and preserved deliverable row IDs during service saves.

Verification:

- `/admin/services/13/edit` switched deliverables English to Spanish and back in the browser.
- Service code, category/type/scope values remained coherent.
- Workflow-stage localization, proposal-title localization, proposal-template localization/deletion, rich text, credentials, and ticket upload/document behavior remained passing.
- Full release gate passed: 146 tests, 1265 assertions; Composer validation/audit clean; production npm audit clean; frontend build passed; route list compiled; migrations applied.

Result: TECHNICALLY READY — FINAL HUMAN APPROVAL PENDING. Production was not accessed or modified.
