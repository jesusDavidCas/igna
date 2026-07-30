# IGNA Studio Phase 5A Human Acceptance Review - 2026 Q3

## Review Identity

- Review branch: `qa/phase-5a-human-acceptance`
- Phase 5A.6 checkpoint: `d661a92511c57aa36bcd00e33fa52c8137f67019`
- Local application URL: `http://127.0.0.1:8000`
- Vite development URL: `http://localhost:5173`
- Production access: not used
- Deployment: not performed
- Human QA status: HUMAN ACCEPTANCE FAILED - CORRECTIVE WORK REQUIRED
- Source corrections: required in Phase 5A.8
- Graphify regeneration: required after corrective source changes
- Phase 5A.8 round-four status: HUMAN ACCEPTANCE FAILED - DYNAMIC LOCALIZATION PROVIDER REMAINS
- Phase 5A.8 round-six status: TECHNICALLY READY - FINAL HUMAN APPROVAL PENDING

## Human QA Finding

HUMAN ACCEPTANCE FAILED - CORRECTIVE WORK REQUIRED

The human review supersedes the earlier local acceptance recommendation. Release preparation must stop until Phase 5A.8 corrects the confirmed defects below.

Update after Phase 5A.8 Round 4: human QA accepted the real credential upload/regeneration/protected-viewer workflow and accepted rich-text bold, italic, clear formatting, bulleted lists, numbered lists, and character counting. Round 4 replaced proposal-template deactivate/reactivate with permanent deletion and expanded locale-aware dynamic content rendering. Release readiness is still blocked by the missing real dynamic translation provider.

Update after Phase 5A.8 Round 6: the remaining confirmed defect was service deliverables staying in their English source language after a global switch to Spanish. Round 6 backfilled catalog deliverable translations, preserved deliverable IDs during service saves, and verified service 13 (`PTP`) switches deliverables English/Spanish in the local browser. The source is technically ready for final human approval; production deployment remains a separate human decision.

## QA Data Strategy

The acceptance pass used only synthetic records labelled with the `QA 5A7` prefix. The synthetic records remain in the local development database so the human reviewer can inspect the same acceptance state.

No credentials, tracking codes, signed URLs, private files, cookies, client content, or private proposal data are recorded in this document.

Local-only evidence was written under:

- `output/ui-review/phase-5a7/`
- `output/credential-review/phase-5a7/`
- `output/mail-review/phase-5a7/`
- `output/proposal-review/phase-5a7/`

## Credential Acceptance

Status: ACCEPTED BY HUMAN QA, READY WITH PRODUCTION PREREQUISITE VALIDATION

- Synthetic team member and credential records were created.
- The original credential remained private.
- A protected derivative was generated successfully.
- The protected derivative was downloadable through the signed credential route.
- Missing derivative access failed closed with a not-found response.
- Response headers included PDF content type, attachment disposition, and `nosniff`.
- PDF inspection confirmed a single-page protected derivative.
- Rendered image review confirmed the visible `IGNA Studio` watermark on the synthetic protected credential.
- Human QA later accepted the corrected real administration upload, regeneration, and protected-viewer path. Production must still confirm GD, Poppler `pdftoppm`, and process functions before relying on credential generation.

Evidence:

- `output/credential-review/phase-5a7/credential-record-summary.json`
- `output/credential-review/phase-5a7/credential-http-summary.txt`
- `output/credential-review/phase-5a7/protected-pdfinfo-summary.txt`
- `output/credential-review/phase-5a7/protected-preview-1.jpg`

## Public Tracking Acceptance

Status: READY

- Public tracking lookup reached the upload form with synthetic ticket data.
- Upload used a non-sensitive local test PDF.
- The page reset after upload.
- A localized explanatory success message appeared.
- Tracking values were absent from the browser URL after upload.
- Re-entering tracking data showed the pending document state.
- No browser console errors were observed.

Evidence:

- `output/ui-review/phase-5a7/public-tracking-upload-summary.json`

## Authenticated Client Upload Acceptance

Status: READY

- The synthetic client could sign in and access the owned ticket.
- The client uploaded a non-sensitive local test PDF.
- The success and pending-review state rendered after upload.
- A different synthetic client was denied access to the ticket through a not-found response.
- Uploaded client and public-tracking files were stored with `pending_review` status and were not immediately client-visible.
- No browser console errors were observed.

Evidence:

- `output/ui-review/phase-5a7/authenticated-client-upload-summary.json`
- `output/ui-review/phase-5a7/ticket-upload-db-summary.json`
- `output/ui-review/phase-5a7/ticket-file-status-summary.json`

## Email Acceptance

Status: READY WITH HUMAN MAIL-PREVIEW CONFIRMATION

- Local mail transport remained configured for logging.
- Queue handling remained local/synchronous in the acceptance environment.
- No mail transport failures were detected in the sanitized local log summary.
- Exact recipient-address and message-body assertions remain covered by automated mail tests.
- Human mail-preview inspection should confirm final English and Spanish message copy before release approval.

Evidence:

- `output/mail-review/phase-5a7/sanitized-mail-log-summary.json`

## Service Administration Acceptance

Status: READY

- Admin service routes rendered with the existing authenticated superadministrator session.
- Synthetic technology and infrastructure services were created with bilingual service content, deliverables, stages, and active category assignments.
- Service administration pages rendered without browser console errors on inspected routes.
- Human QA confirmed the administration UI still shows redundant bilingual fields for service deliverables and workflow stages.
- Human QA confirmed Spanish values repeat English content in places and per-section translation actions do not produce useful translated content.

Evidence:

- `output/ui-review/phase-5a7/browser-acceptance-summary.json`
- `output/ui-review/phase-5a7/browser-id-route-summary.json`
- `output/ui-review/phase-5a7/cross-module-db-summary.json`

## Grouped Public Request Acceptance

Status: READY WITH HUMAN FINAL VISUAL CONFIRMATION

- The public request form rendered persisted active services under grouped category headings.
- Technology and Infrastructure Engineering groups were present in local browser checks.
- The "Other / I am not sure" option remained available.
- Mobile and desktop checks showed no horizontal overflow, no duplicate DOM IDs, and no browser console errors.
- Selection persistence after validation failure remains covered by automated tests.

Evidence:

- `output/ui-review/phase-5a7/browser-acceptance-summary.json`
- `output/ui-review/phase-5a7/responsive-summary.json`

## Proposal Template Acceptance

Status: CORRECTIVE WORK REQUIRED

- The proposal-template catalogue rendered in the admin panel.
- The synthetic template contained multiple reusable cost rows.
- Proposal editor template controls rendered on desktop without console errors.
- Active template insertion and repeated insertion behavior remain covered by focused tests.
- Template snapshot independence was verified through database inspection and automated tests.
- Round 4 removed deactivate/reactivate as the normal workflow and added permanent deletion. Existing inactive templates remain visible as legacy records until a human intentionally deletes them. Historical proposal snapshots remain independent.

Evidence:

- `output/ui-review/phase-5a7/browser-id-route-summary.json`
- `output/ui-review/phase-5a7/responsive-summary.json`
- `output/ui-review/phase-5a7/cross-module-db-summary.json`

## Proposal PDF Acceptance

Status: ACCEPTED BY HUMAN QA, READY WITH FINAL PDF VISUAL CONFIRMATION

- A synthetic proposal PDF was generated locally through the application PDF view path.
- PDF inspection confirmed a single-page landscape PDF.
- Saved proposal item values remained independent from reusable template rows.
- Human visual approval should confirm final formatting in a browser/PDF viewer before release approval.
- Human QA accepted bold, italic, clear formatting, bulleted lists, numbered lists, and character counting after Phase 5A.8 corrections.

Evidence:

- `output/proposal-review/phase-5a7/qa-5a7-proposal.pdf`
- `output/proposal-review/phase-5a7/proposal-pdf-summary.json`
- `output/proposal-review/phase-5a7/proposal-pdfinfo-summary.txt`

## Cross-Module Synchronization

Status: READY

- Public services, proposal templates, proposal snapshots, tickets, ticket documents, and credentials remained independent.
- Service category changes did not affect proposal templates.
- Template rows did not become public service deliverables.
- Saved proposal rows were independent from reusable template rows.
- Ticket document review state remained independent from service workflow configuration.

Evidence:

- `output/ui-review/phase-5a7/cross-module-db-summary.json`
- Focused and full automated test gates listed in the final release approval report.

## Localization Review

Status: CORRECTIVE WORK REQUIRED

- Public tracking and authenticated client upload success flows rendered localized messages.
- Grouped public selector labels rendered in the current interface locale.
- English and Spanish mail behavior remains covered by automated tests.
- Human review should approve final wording in English and Spanish before release approval.
- Round 4 corrected locale-aware rendering for stored service parent content, deliverables, workflow stages, proposal titles, and proposal-template rows when target-locale values exist. Automatic translation remains blocked until a real provider is configured and smoke-tested.

## Browser Console And Network Review

Status: READY

- Inspected admin, public, mobile, and desktop routes showed no browser console errors.
- Duplicate DOM ID checks returned zero on inspected pages.
- Mobile public request and template catalogue pages did not overflow horizontally.
- Route list was captured for review.

Evidence:

- `output/ui-review/phase-5a7/browser-acceptance-summary.json`
- `output/ui-review/phase-5a7/browser-id-route-summary.json`
- `output/ui-review/phase-5a7/responsive-summary.json`
- `output/ui-review/phase-5a7/route-list.txt`

## Confirmed Release Blockers

- No confirmed source-level release blocker remains after Round 6 local verification.
- No real dynamic-content translation provider is configured for automatic database-content translation.

## Functional Corrections Required

- Complete final human approval using the Round 6 checklist before production deployment.
- Confirm production capability prerequisites before relying on credential generation.
- Re-run any older failed credential records the human expects to recover; missing originals must be reuploaded.

## Corrections Made

Phase 5A.8 corrections remain unstaged. The second corrective round added real credential upload/regeneration validation, a one-visible-field proposal-template row model, provider-backed local translation sync tooling, and richer browser/PDF list handling. Human QA must repeat acceptance before release preparation.

## Human Checklist

Use the retained synthetic `QA 5A7` records and new Phase 5A.8 evidence folders after corrective work is complete.

| Area | Reviewer action | Expected result |
| --- | --- | --- |
| Credential | Upload a credential through the real administration interface and view the protected copy inline. | Protected derivative generates successfully; original is not served; watermark is visible. |
| Credential | Attempt the missing-derivative case if retained. | Access fails closed with not-found behavior. |
| Public tracking | Open public tracking, use the synthetic ticket data, and inspect uploaded documents. | Pending document appears; URL does not expose tracking data. |
| Client upload | Sign in as the synthetic owning client and inspect the ticket. | Uploaded document is pending review and localized success is visible. |
| Client isolation | Sign in as the different synthetic client and open the same ticket route. | Access is denied. |
| Mail | Inspect English and Spanish local mail previews. | No attachments, private paths, recipient leakage, or unsafe links. |
| Services | Edit a synthetic service in the current locale. | One visible field per content item; translation cache updates safely; ordering and category assignment persist. |
| Public request | Open the public request form in both locales. | Technology and Infrastructure Engineering groups render; "Other" remains available. |
| Proposal templates | Create, duplicate, edit, and permanently delete a disposable synthetic template. | Catalogue is clean; Edit/Editar labels are correct; one visible title field is shown; deleted templates disappear without changing historical proposals. |
| Proposal rich text | Use bold, italic, clear formatting, bulleted lists, numbered lists, and boundary-length content. | Formatting persists, lists render semantically, PDF output preserves lists, and over-limit content is rejected without truncation. |
| Proposal PDF | Open the generated synthetic proposal PDF. | PDF reflects saved proposal items, not later template changes. |
| Responsive | Check public request and proposal-template pages on mobile width. | No broken layout or horizontal overflow. |

## Remaining Human-Review Items

- Phase 5A.8 correction and regression validation.
- Human visual approval of the protected credential derivative after real administration upload succeeds.
- Human visual approval of the generated proposal PDF.
- Human copy approval for English and Spanish upload notification messages.
- Production prerequisite confirmation for credential generation: PHP GD, `pdftoppm`, `proc_open`, `proc_get_status`, and `proc_close`.
- Production deployment and production smoke test remain separate human-controlled activities.
