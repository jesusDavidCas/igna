# Phase 5A.3 - Service Administration Structured Content

## Objective

Implement the local Phase 5A.3 service administration improvements on top of the Phase 5A.2 checkpoint.

## Scope

- Complete bilingual service-name and supported service-content translation.
- Replace delimiter-based deliverable editing with structured repeatable bilingual rows.
- Reuse the existing `service_deliverables` relationship for ordered deliverable storage.
- Make service workflow-stage administration compact and scannable.
- Preserve legacy service, deliverable, and workflow-stage content.
- Add focused automated tests and run full local verification.

## Constraints

- Do not push, merge, deploy, or access production.
- Do not change ticket lifecycle behavior.
- Do not change credential-protection, ticket-upload notification, proposal calculation, or authentication behavior.
- Do not update dependencies.
- Do not modify `.env`.
- Do not stage or commit Phase 5A.3 automatically.
- Do not expose local superadministrator credentials.

## Required Validation

- Verify Phase 5A.2 is checkpointed first.
- Create and work on `feat/service-administration-structured-content`.
- Test additive migration apply, rollback, and reapply.
- Run focused service, credential, ticket upload, ticket workflow, and proposal tests.
- Run the full application suite, dependency audits, frontend build, diff check, migration status, and graph update.
- Leave local browser validation for integrated human review when credentials are not securely available to automation.
