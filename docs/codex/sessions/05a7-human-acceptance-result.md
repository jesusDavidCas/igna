# Phase 5A.7 Human Acceptance Result

## Summary

Phase 5A.6 was checkpointed as `d661a92511c57aa36bcd00e33fa52c8137f67019` with message `docs: record phase 5a integrated release review`. The human acceptance branch `qa/phase-5a-human-acceptance` was created directly from that commit.

Phase 5A.7 exercised the local application with synthetic `QA 5A7` records and initially found no release-blocking defect. Subsequent human QA superseded that recommendation and confirmed one release blocker plus multiple functional corrections. No dependency changed, no production access occurred, and no deployment was executed.

Final Phase 5A.7 status: HUMAN ACCEPTANCE FAILED - CORRECTIVE WORK REQUIRED.

## Branch And Checkpoint

- Starting branch: `qa/phase-5a-integrated-release-candidate`
- Phase 5A.6 commit: `d661a92511c57aa36bcd00e33fa52c8137f67019`
- Acceptance branch: `qa/phase-5a-human-acceptance`
- Acceptance branch HEAD: `d661a92511c57aa36bcd00e33fa52c8137f67019`

## Local Application State

- Laravel local server reached `http://127.0.0.1:8000`.
- Vite development server reached `http://localhost:5173`.
- Existing authenticated superadministrator session was available for admin checks.
- Production was not accessed.

## Synthetic QA Records

The pass created synthetic local-only records labelled `QA 5A7` for:

- Client users.
- Public services in technology and infrastructure categories.
- A ticket and ticket documents.
- A team member credential.
- A proposal service template.
- A proposal and proposal PDF.

Sensitive values used during browser automation were not written to source, docs, screenshots, or committed files.

## Browser Acceptance

Admin and public pages rendered without console errors on inspected routes:

- Admin dashboard.
- Team member credential administration.
- Service administration.
- Public grouped request form.
- Proposal-template catalogue.
- Proposal editor.

Responsive checks showed no mobile overflow, no duplicate DOM IDs, and no browser console errors for inspected public and admin pages.

## Upload Acceptance

Public tracking upload:

- Lookup succeeded.
- Upload used a non-sensitive local PDF.
- Page reset after upload.
- Success messaging was localized.
- Tracking values were absent from the URL after upload.
- Pending document state was visible after re-entering tracking data.

Authenticated client upload:

- Synthetic client could upload to an owned ticket.
- Success and pending-review state rendered.
- A different synthetic client was denied access to the ticket.
- Uploaded files were stored as `pending_review` and were not immediately client-visible.

## Credential Acceptance

- Protected derivative generation succeeded.
- Download route returned the protected PDF.
- Missing derivative failed closed with a not-found response.
- PDF metadata confirmed the generated protected derivative.
- Rendered image review confirmed a visible `IGNA Studio` watermark.
- Human QA later confirmed protected derivative generation failed through the real administration interface; this is a release blocker for Phase 5A.8.

## Proposal Acceptance

- Proposal-template catalogue and edit routes rendered.
- Proposal editor showed active template insertion controls.
- Synthetic proposal PDF generation succeeded.
- Saved proposal rows remained independent from reusable template rows.
- Human QA later confirmed catalogue noise, incorrect labels, duplicated template title inputs, non-working rich-text list controls, and unsynchronized character-limit behavior.

## Human QA Blockers

- Protected credential derivative generation failed through the real administration interface.
- Service deliverables show duplicate English and Spanish inputs.
- Spanish values repeat English content.
- Service workflow-stage editing shows duplicate bilingual fields.
- Per-section translation actions do not produce useful translated content.
- Proposal-template catalogue contains noisy or unexplained counters.
- Proposal-template edit label is incorrectly localized.
- Proposal-template editing duplicates language inputs.
- Proposal rich-text bullet and numbered-list controls do not work.
- Character-limit behavior requires synchronization.

## Mail Acceptance

- Local mail transport remained configured for log output.
- No mail transport failures were detected in sanitized local log evidence.
- Exact mail recipient and content behavior remains covered by automated tests.
- Human bilingual mail-preview approval remains required.

## Test Results

- `TeamCredentialProtectionTest`: 4 passed, 28 assertions.
- `TicketLayoutDocumentExchangeTest`: 14 passed, 208 assertions.
- `ServiceAdministrationStructuredContentTest`: 9 passed, 54 assertions.
- `PublicServiceTaxonomyRequestTest`: 9 passed, 62 assertions.
- `ProposalCostTemplateCatalogueTest`: 7 passed, 56 assertions.
- Serial integrated regression band: 115 passed, 1068 assertions.
- Full Laravel suite: 133 passed, 1121 assertions.
- `composer validate`: passed.
- `composer audit --locked`: no advisories.
- `npm audit --omit=dev`: zero vulnerabilities.
- `npm run build`: passed with the known Node `DEP0205` warning.
- `git diff --check`: passed.
- `php artisan route:list --except-vendor`: captured to local evidence.
- `php artisan migrate:status`: captured to local evidence; local migrations were applied.

## Evidence

Evidence remains local-only and uncommitted:

- `output/ui-review/phase-5a7/`
- `output/credential-review/phase-5a7/`
- `output/mail-review/phase-5a7/`
- `output/proposal-review/phase-5a7/`

## Corrections

No corrective source work was performed in Phase 5A.7. Phase 5A.8 must correct the human-confirmed blockers.

## Remaining Human Review

- Visual approval of the protected credential derivative.
- Visual approval of the synthetic proposal PDF.
- English and Spanish mail-preview copy approval.
- Production capability confirmation for PHP GD, `pdftoppm`, `proc_open`, `proc_get_status`, and `proc_close`.
- Production deployment through the approved human-controlled runbook.

## Final Recommendation

Phase 5A.7 is not ready for human release approval. Complete Phase 5A.8 corrective stabilization and repeat integrated human acceptance.
