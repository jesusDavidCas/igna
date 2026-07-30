# Phase 5A.8 Human QA Corrections

Date: 2026-07-30

Branch: `fix/phase-5a-human-qa-corrections`

Base checkpoint: `7cae3fb9f2b14bf488bfed05925926b874ff4a8a`

## Result

HUMAN ACCEPTANCE FAILED — DYNAMIC LOCALIZATION AND TEMPLATE DELETION REMAIN.

The first Phase 5A.8 corrective round was rejected by human QA because real browser review still showed credential generation, dynamic database localization, proposal-template row editing, and rich-text list failures. A second corrective round was started on the same branch and remains unstaged and uncommitted for human review.

Round 4 status: not ready for release approval. Human QA accepted the real credential upload/regeneration/protected-viewer path and accepted the rich-text toolbar behavior for bold, italic, clear formatting, bulleted lists, numbered lists, and character counting. Round 4 replaced the normal proposal-template removal workflow with safe permanent deletion and extended locale-aware database rendering across service parent fields, deliverables, workflow stages, proposal titles, and proposal-template row descriptions. Automatic dynamic translation remains blocked because no real translation provider is configured.

## Reproduced Defects

- Protected credential PDF generation needed better failure diagnostics around Poppler output handling.
- Public credential PDFs needed an inline protected viewer instead of a prominent protected-download path.
- Service administration exposed duplicate bilingual input fields during normal editing.
- The default dynamic-content translator copied source text into the target locale, creating a false translation.
- Proposal-template catalogue cards showed noisy implementation detail and had a missing edit label translation.
- Proposal-template title editing exposed duplicate locale title fields.
- Proposal rich-text list controls depended on fragile browser editing behavior.
- Rich-text client counters and server-side validation were not explicitly synchronized on visible-character limits.

## Credential Correction

`CredentialPreviewRenderer` now runs `pdftoppm` with explicit success checking, accepts both JPG and JPEG output naming, filters empty files, and records sanitized process output on failure. The standard public credential file response now uses `Content-Disposition: inline` while still serving only the protected derivative.

The public credential page embeds protected PDFs through a signed inline `<object>` with an `<iframe>` fallback and disables the browser PDF toolbar where supported. Missing protected derivatives continue to fail closed and do not expose the original file.

Second corrective round added explicit Poppler executable configuration and fallback discovery for the local Codex runtime path. The real local validation file was `output/credential-review/phase-5a1/Master.pdf`; its SHA-256 was `d400423cb7a99a195d6ee76e29aa61e04dfd84d694b1daa30c46841832d97865`. That file passed direct renderer validation, real admin HTTP upload, protected-derivative creation, protected viewer opening, and real regeneration through the admin form. The uploaded source hash matched the selected file hash and the protected derivative was non-empty.

Credential record classification in the local database separated ready records from missing-derivative records and stale-generation-warning records. Older failed records with private originals may need human-triggered regeneration; missing originals must be reuploaded.

Local prerequisite check:

- PHP GD: available locally.
- `pdftoppm`: available locally.
- `proc_open`, `proc_get_status`, `proc_close`: available locally.

Production must still independently confirm these capabilities before accepting credential generation as production-ready.

## Translation Architecture

No fake dynamic translation is performed. The default translation service now fails honestly when no provider is configured. Controllers preserve existing usable translated cache values, but they do not write copied source text as a target-locale cache. Administrators edit one source-language field in the current interface language; hidden cache fields preserve existing valid legacy translations.

Second corrective round added `content:translate-missing` as a local-only synchronization command. Round 4 expanded it to include proposal titles and to refuse unrestricted non-dry-run mutation unless a specific service, proposal template, or proposal ID is selected. The command replaces missing or copied target-locale caches only through the configured provider, skips valid human translations, and fails closed when the provider is unavailable. It does not print provider secrets or content values.

Provider status: no real dynamic translation provider is configured in the repository. This is a release blocker under the Phase 5A.8 acceptance prompt.

## Service Administration

The normal service and workflow-stage forms now expose one visible source-language field for name, description, and deliverables. Existing bilingual data remains readable through model localization and hidden cache preservation. Legacy deliverable normalization accepts the new `content` field while preserving prior newline and pipe formats.

Round 4 verified that the global locale now controls database-backed service parent names and descriptions, deliverable labels, workflow-stage names, and workflow-stage descriptions when stored target-locale values exist. The Spanish service catalogue fallback remains available for legacy seed services whose Spanish relation rows are missing.

The complete service graph handled by the sync command is:

- service name and description;
- every submitted deliverable label and description field when present;
- every workflow-stage name and description.

Ticket localization was explicitly bounded: system-defined statuses, reusable stage labels, and localized notification templates switch locale; ticket codes, uploaded filenames, client-authored subjects/messages, personal names, email addresses, and private notes remain source-only or language-neutral and are not sent to an external translator.

## Proposal Localization

Round 4 added nullable `title_en` and `title_es` columns to proposals and backfilled `title_en` from the legacy `title`. Proposal create/update now writes the visible title into the current interface locale, preserves a valid opposite-locale value, and attempts provider-backed generation only when a real provider is available. Admin lists, show pages, dashboard cards, public proposal headers, WhatsApp text, and proposal PDFs render through `localizedTitle()`. Prices, quantities, calculations, saved proposal item snapshots, and public proposal tokens remain unchanged.

## Proposal Templates

The catalogue now focuses cards on template title, status, and actions. Global count chips, service-number prefixes, item-count noise, and sort-order noise were removed from the card display. The missing `site.edit` label was added in English and Spanish.

Proposal-template title editing now uses a single visible title field for the current interface language and preserves valid target-locale cache separately. Reusable cost-row descriptions now follow the same single visible current-locale field model. Hidden cache fields preserve valid target-locale values, and focused tests cover English and Spanish row rendering with a deterministic fake provider.

Round 4 removed the normal deactivate/reactivate workflow. The catalogue now shows all templates, including legacy inactive templates, and provides a CSRF-protected `DELETE` action with localized permanent-action confirmation. Deleting a template runs in a transaction, deletes only the reusable template and its child cost rows, and leaves proposals, proposal items, proposal PDFs, tickets, and public services untouched. The `is_active` column remains for backward compatibility and for discovering legacy inactive records until the human deletes or edits them deliberately.

## Rich Text

The proposal rich-text toolbar now inserts semantic ordered and unordered lists through deterministic DOM operations instead of relying solely on `execCommand` list behavior. The visible-character limit is declared in the editor wrapper and enforced server-side against sanitized plain text without truncating submitted content.

Second corrective round added Range preservation, selected-fragment line extraction, Enter/beforeinput caret handling, scoped list-marker CSS for the browser editor, and explicit PDF list styles. Browser validation confirmed pasted multi-line content can be converted to `<ul><li>` and `<ol><li>`, saved, reloaded, and rendered with visible markers in the generated PDF. Round 4 records the latest human finding that rich-text bold, italic, clear formatting, bulleted lists, numbered lists, and character counting are accepted; no rich-text redesign was performed.

## Browser Review

Local browser evidence was saved under ignored `output/` directories. The browser review covered:

- Service edit form: no visible duplicate locale fields and no fake translation controls.
- Proposal-template catalogue: no raw translation key, no service-number prefix, no count/order noise, and visible edit action label.
- Proposal-template edit form: one visible template title field.
- Proposal edit form: rich-text editors, toolbar commands, and `10,000` visible-character counters.
- Synthetic local protected credential: inline PDF object/iframe, signed derivative file route, inline PDF headers, and no private storage-path text.
- Existing missing-derivative credential: fail-closed message confirmed.
- Real Master PDF upload through the team administration form: protected derivative ready, source hash matched, and regeneration succeeded.
- Rich-text pasted bullet and numbered list flows: semantic list markup persisted after save/reload.
- Proposal PDF: Dompdf generated a non-empty PDF and rasterized first page showed visible bullet and numbered markers.

The in-app PDF viewer emitted one `MutationObserver` console error while rendering the embedded PDF. No matching project source was found; other reviewed app pages showed no browser console errors.

## Automated Verification

Focused blocker band:

- `TeamCredentialProtectionTest`
- `PublicPlatformTest`
- `ServiceAdministrationStructuredContentTest`
- `PublicServiceTaxonomyRequestTest`
- `ProposalCostTemplateCatalogueTest`
- `ProposalAdministrationUsabilityTest`

First corrective round result: 55 tests, 477 assertions passing before the final label correction; affected rerun after the label correction passed 35 tests and 343 assertions.

Second corrective round focused result:

- `ProposalCostTemplateCatalogueTest`: 10 tests, 98 assertions passing.
- `ServiceAdministrationStructuredContentTest`: 12 tests, 84 assertions passing.
- `ProposalCostTemplateCatalogueTest`, `ProposalAdministrationUsabilityTest`, `TeamCredentialProtectionTest`, `PublicPlatformTest`: 36 tests, 360 assertions passing.

Round 4 focused result before the full gate:

- `ProposalCostTemplateCatalogueTest`: 12 tests, 119 assertions passing.
- `ServiceAdministrationStructuredContentTest`: 13 tests, 96 assertions passing.
- `ProposalAdministrationUsabilityTest`: 10 tests, 141 assertions passing.
- Phase 5A regression band (`TeamCredentialProtectionTest`, `TicketLayoutDocumentExchangeTest`, `TicketWorkflowIntegrityTest`, `PublicServiceTaxonomyRequestTest`, `ServiceAdministrationStructuredContentTest`, `ProposalCostTemplateCatalogueTest`, `ProposalAdministrationUsabilityTest`): 76 tests, 803 assertions passing.

Full regression:

- `php artisan test`: 144 tests, 1244 assertions passing.
- `composer validate`: passing.
- `composer audit --locked`: no advisories.
- `npm audit --omit=dev`: zero vulnerabilities.
- `npm audit`: development-only advisories remain in Vite/PostCSS/shell-quote tooling.
- `npm run build`: passing with the existing Node `module.register()` deprecation warning.
- `git diff --check`: clean.
- `php artisan route:list --except-vendor`: captured locally.
- `php artisan migrate:status`: all local migrations applied, including `2026_07_30_000300_add_localized_title_to_proposals`.
- Additive migration rehearsal: disposable SQLite apply, rollback one step, and reapply passed.

## Graphify

Graphify was refreshed after the corrections:

- Nodes: 2093.
- Edges: 3582.
- Communities: 246.
- Warning: `skills-lock.json` produced zero nodes, consistent with a non-code lock artifact.

## Round 4 Browser Acceptance

Local browser checks on `http://127.0.0.1:8000` used existing synthetic QA records only. The proposal-template catalogue showed permanent Delete/Eliminar behavior with no Deactivate/Reactivate controls and no console errors. The public grouped request selector rendered persisted Spanish QA services under the Technology and Infrastructure Engineering groups with the "Other / No estoy seguro" option. The admin service edit page switched the synthetic service parent name, description, first deliverable, and first workflow-stage name between English and Spanish. The proposal-template edit page switched the synthetic template title and first row description between English and Spanish. No provider-backed automatic translation was smoke-tested because no real provider is configured.

## Round 6 Deliverable Finalization

Human QA narrowed the remaining localization defect to service deliverables. Service 13 (`PTP`) reproduced the problem: the Spanish interface correctly rendered the localized service parent name and description through the catalog fallback, but the persisted `service_deliverables` rows had English `name`/`name_en` values and blank `name_es` values, and the Spanish language catalog did not include deliverable lists for engineering services. As a result, `Service::localizedDeliverables()` had no valid Spanish value and fell back to English.

The correction keeps the one-visible-field administration model and makes the persisted graph stable:

- Added English and Spanish catalog deliverable translations for engineering services.
- Added `2026_07_30_000400_backfill_catalog_service_deliverable_locales.php` to backfill existing catalog deliverable `name_en`/`name_es` values without changing IDs or order.
- Updated the service catalog seeder so fresh databases create bilingual service parent and deliverable rows.
- Added hidden deliverable IDs to the service form and request normalization.
- Changed service saves to update retained deliverables by ID, create only new rows, and delete only removed rows.
- Preserved reviewed target translations and avoided copied-source target caches.

Round 6 browser validation on `/admin/services/13/edit` confirmed:

- English deliverables: `project descriptive report`, `hydraulic calculations`, `technical plans`, `Resolution 799 of 2021 references`.
- Spanish deliverables: `memoria descriptiva del proyecto`, `cálculos hidráulicos`, `planos técnicos`, `referencias de la Resolución 799 de 2021`.
- Switching back to English restored the English values.
- Service code `PTP` remained unchanged.
- Workflow-stage names still switched between English and Spanish.
- No project console errors were observed.

The public homepage does not render service deliverable lists; public service names and descriptions continued to switch locale in the browser check.

Round 6 verification:

- Focused tests: `ServiceAdministrationStructuredContentTest` 15 tests / 117 assertions; `PublicServiceTaxonomyRequestTest` 9 / 62; `ProposalCostTemplateCatalogueTest` 12 / 119; `ProposalAdministrationUsabilityTest` 10 / 141; `TeamCredentialProtectionTest` 4 / 28; `TicketLayoutDocumentExchangeTest` 14 / 208.
- Full suite: `php artisan test` 146 tests / 1265 assertions.
- `composer validate`: passed.
- `composer audit --locked`: no advisories.
- `npm audit --omit=dev`: zero vulnerabilities.
- `npm run build`: passed with the known Node `DEP0205` warning.
- `git diff --check`: clean.
- `php artisan migrate:status`: all local migrations applied.
- Disposable SQLite migration rehearsal: apply, rollback, and reapply passed.

Human approval is still required before production deployment. Technical source readiness is no longer blocked by the deliverable-localization defect.

## Remaining Limitations

- Production credential generation still requires Hostinger capability confirmation for GD, Poppler `pdftoppm`, and process functions.
- No dynamic translation provider is configured; this is explicit rather than hidden by copied text and blocks readiness.
- Real-provider smoke tests in both directions were not possible locally because no real provider adapter or credential is configured.
- Full development `npm audit` still reports development-tool advisories; the production audit is clean.
- The embedded PDF viewer console error should be rechecked during human browser acceptance in the target browser.

## Final Working State

Phase 5A.8 corrections are intentionally unstaged and uncommitted. No push, merge, deployment, production access, dependency upgrade, migration, or environment-file edit was performed.
