# Phase 1E Prompt

Implement Phase 1E for IGNA Studio ticket workflow integrity:

- Redesign administrator deliverables as full-width vertically stacked sections below the project timeline.
- Add a client-submitted document lifecycle: pending review, downloaded by IGNA, reviewed, rejected.
- Record first successful administrator download without treating download as review.
- Add explicit administrator review/reject controls for client-submitted documents only.
- Enforce strictly sequential stage movement: complete current stage to advance exactly one stage, and reopen only the immediately previous stage to roll back exactly one stage.
- Supersede client-facing messages from abandoned stage attempts while retaining administrator audit history.
- Preserve Phase 1 and Phase 1D invariants: localized ticket emails, explicit-only stage completion, private file access, signed tracking context, secure client uploads, no proposal/payment/status side effects, and no dependency or environment changes.

Required verification:

- Focused tests for deliverable UX, document review, and sequential rollback.
- Full `php artisan test`.
- `npm run build`.
- `git diff --check`.
- Local additive migration apply, rollback, reapply.
- Durable session documentation and human visual test guide.
