# Phase 5A.4 - Grouped Public Service Selection Prompt

## Objective

Connect the Phase 5A.3 structured service administration work to the public "Tell us what you need" request form.

The public request form must stop presenting one long flat service list and instead render active database services under bilingual business categories:

- Technology
- Infrastructure Engineering
- Other / I am not sure

The exact service names must continue to come from the database. The implementation must not hard-code a fictional service inventory or create a broad taxonomy platform.

## Repository And Branch

- Repository: `/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform`
- Phase 5A.3 source branch: `feat/service-administration-structured-content`
- Phase 5A.4 branch: `feat/grouped-public-service-selection`
- Phase 5A.3 checkpoint commit: `10c4ad180917a70223c93b6a6d3d7d0ee3bc946f`

## Required Scope

Allowed:

- Public request form service selector.
- Service business-line/category assignment.
- Bilingual category labels.
- Category display order.
- Active/inactive filtering.
- Minimal service administration category control.
- Validation.
- Request persistence.
- Public request email/display compatibility.
- Additive migration when necessary.
- Focused tests.
- Browser validation or clearly documented pending browser review.
- Phase 5A.4 documentation.

Excluded:

- Phase 5A.3 deliverable redesign.
- Phase 5A.3 workflow-stage redesign.
- Proposal cost-template catalogue.
- Proposal calculations.
- Ticket workflow redesign.
- Credential protection redesign.
- Notification redesign beyond compatibility labels.
- Landing-page redesign.
- Broad taxonomy platform.
- Multi-level category trees.
- Drag-and-drop category builders.
- Production deployment.
- Dependency upgrades.

## Reconnaissance Requirements

Inspect before editing:

- Public landing route, controller, view, service selector, selected-value retention, locale handling, validation, and mobile-compatible markup.
- Public request form request, persistence path, ticket creation, notification emails, and existing request tests.
- Existing `Service` classification fields, especially `business_line`, `service_type`, `service_scope`, `sort_order`, and active flags.
- Service administration create/edit flow and validation.
- Public, admin, client, email, ticket, proposal, and test consumers of service selection.

## Taxonomy Decision Requirements

Use the smallest coherent architecture:

1. Reuse an existing field when it already represents Technology versus Infrastructure Engineering.
2. Add a constrained language-neutral category code only if no suitable field exists.
3. Create a category table only if independently managed categories are genuinely required.

Category codes must be language-neutral. Display labels must come from translation files.

## Public Selector Requirements

- Use native `<optgroup>` when the existing UI uses a select.
- Category headings must be visible and non-selectable.
- Services must appear beneath their category.
- Only active services should appear.
- Empty groups should not render.
- "Other / I am not sure" must remain selectable.
- Selected values must survive validation errors.
- Category headings must not be valid request submissions.
- Service option values must remain stable service ids.
- Desktop, mobile, keyboard, and screen-reader behavior must remain usable.

## Other Request Requirements

When the client selects "Other / I am not sure":

- Permit the existing free-text request description.
- Do not create a fake `Service` record.
- Preserve notifications.
- Show the localized label.
- Retain the selection after validation errors.
- Do not assign the request automatically to Technology or Infrastructure Engineering.

## Verification Requirements

Run focused Phase 5A.4 tests first, then:

- `php artisan test`
- `composer audit --locked`
- `npm audit --omit=dev`
- `npm run build`
- `git diff --check`
- `php artisan migrate:status`
- `git status --short`
- `git diff --stat`

When a migration exists, apply locally, test rollback, reapply, rerun focused tests, and rerun full tests.

## Staging And Commit

Do not stage or commit Phase 5A.4 automatically. Leave the implementation unstaged for the integrated human browser review with Phase 5A.3.
