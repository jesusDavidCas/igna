# Phase 5A.5 - Proposal Cost-Template Catalogue Result

## Phase 5A.4 Checkpoint

- Phase 5A.4 branch: `feat/grouped-public-service-selection`
- Phase 5A.4 checkpoint commit: `5934b80baa04bcec259b16bf6c826a24f3d91a3a`
- Phase 5A.4 commit message: `feat: group public services by business category`
- Phase 5A.5 branch: `feat/proposal-cost-template-catalogue`

## Existing Proposal-Template Architecture

The application already had a database-backed reusable proposal template model:

- `App\Models\ProposalServiceTemplate`
- `App\Models\ProposalServiceTemplateItem`
- `proposal_service_templates`
- `proposal_service_template_items`
- `ProposalController::proposalTemplates()`
- `ProposalController::proposalTemplatePayload()`
- `resources/views/admin/proposals/partials/form.blade.php`

The proposal editor already copied template item values into normal proposal item rows. Saved proposals therefore use snapshot data in `proposal_items`, not live foreign-key references back to templates.

## Catalogue Architecture Decision

No new table or migration was added. The existing template tables already support:

- bilingual template titles;
- active/inactive state;
- display number;
- display ordering;
- multiple cost rows;
- bilingual row descriptions;
- row code, unit, quantity, and unit value.

The implementation adds a dedicated admin controller, request, views, routes, navigation, translations, and tests around that existing source of truth.

## Admin Catalogue

Added routes under the existing authenticated admin role boundary:

- `admin.proposal-templates.index`
- `admin.proposal-templates.create`
- `admin.proposal-templates.store`
- `admin.proposal-templates.edit`
- `admin.proposal-templates.update`
- `admin.proposal-templates.duplicate`
- `admin.proposal-templates.status`

Superadministrators and administrators can manage proposal cost templates because they already can manage proposals. Clients and guests are denied by existing route middleware.

## Template Titles And Localization

Templates require English and Spanish titles. The model now falls back to the alternate title when the active locale field is unexpectedly empty.

The proposal editor now says:

- English: `Manage proposal templates`
- Spanish: `Administrar plantillas de propuestas`

Legacy public-service labels remain separate.

## Active And Inactive Templates

The catalogue lists both active and inactive templates.

Active templates appear in the proposal editor selector.

Inactive templates remain editable in the catalogue but are hidden from proposal insertion.

Duplicated templates are created as inactive drafts to avoid accidentally publishing a copied cost structure before review.

## Display Ordering

Template ordering uses `sort_order`, then `service_number`, then `id` for deterministic rendering.

## Cost-Row Administration

Template rows support:

- row code;
- English description;
- Spanish description;
- unit;
- integer quantity;
- decimal unit value.

Blank rows are filtered before validation. At least one bilingual row is required.

## Proposal-Editor Integration

The proposal editor still appends selected template rows without replacing existing proposal items. The management link now points to the dedicated proposal-template catalogue instead of public service administration.

## Historical Proposal Immutability

Focused tests confirm that after template rows are inserted into a proposal, later template title, row, price, or active-state changes do not mutate saved proposal items or PDF output.

## Public-Service Independence

Public service administration and public request taxonomy remain separate. Updating proposal templates does not rewrite public services, and updating public services does not rewrite proposal templates.

## Schema Impact

No Phase 5A.5 migration was required.

## Verification Snapshot

Focused catalogue test:

- `php artisan test --filter=ProposalCostTemplateCatalogueTest`
- Result: 7 passed, 56 assertions.

Regression band:

- `php artisan test --filter='ProposalCostTemplateCatalogueTest|ProposalAdministrationUsabilityTest|AdminOperationsTest|PublicServiceTaxonomyRequestTest|ServiceAdministrationStructuredContentTest'`
- Result: 60 passed, 509 assertions.

Full verification results are recorded in the final response for the execution that produced this file.

Full suite:

- `php artisan test`
- Result: 133 passed, 1121 assertions.

Release-style local gates:

- `composer validate`: valid.
- `composer audit --locked`: no security vulnerability advisories found.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm audit`: reports dev-tool advisories in `postcss`, `vite`, and `concurrently` via `shell-quote`; production audit remains clean and dependency updates were outside Phase 5A.5 scope.
- `npm run build`: passed with existing Node `DEP0205` deprecation warning.
- `git diff --check`: passed.
- `php artisan migrate:status`: no Phase 5A.5 migration; latest local migration remains `2026_07_30_000200_allow_other_public_service_requests` as batch 8.

## Browser Validation

Local Laravel was started with `php artisan serve --host=127.0.0.1 --port=8000`.

Unauthenticated browser checks:

- Homepage loaded at `http://127.0.0.1:8000/`.
- `/admin/proposal-templates` redirected to `/login`.
- Browser console error check was clean.

Authenticated local admin checks:

- Local seeded admin login succeeded.
- `/admin/proposal-templates` rendered `Plantillas de propuestas`.
- The page showed catalogue intro text and the new-template action.
- `/admin/proposals/create` rendered the proposal template select, copy input, append button, admin navigation link, and `Administrar plantillas de propuestas` editor link to `/admin/proposal-templates`.
- Browser console error check was clean.

The live browser homepage database state did not show grouped public-service labels during this pass, while the seeded automated public taxonomy tests did pass. Public selector review should remain part of the integrated human review if the local browser database is not reseeded.

## Integrated Review Guide

Admin catalogue review:

1. Sign in locally as a superadministrator or administrator.
2. Open Proposal templates from the admin panel.
3. Create a template with English and Spanish titles.
4. Add multiple cost rows.
5. Save, edit, duplicate, deactivate, and reactivate the template.
6. Confirm duplicated templates are inactive drafts.

Proposal editor review:

1. Open the proposal create form.
2. Confirm active templates appear in the selector.
3. Confirm inactive templates do not appear.
4. Append a template once and multiple times.
5. Save a proposal.
6. Edit or deactivate the source template.
7. Confirm the saved proposal and PDF still show the originally saved item rows.

Public-service review:

1. Open service administration.
2. Confirm public service categories and services are unchanged.
3. Confirm proposal template edits do not affect public request selection.

## Files Changed

- `app/Http/Controllers/Admin/ProposalServiceTemplateController.php`
- `app/Http/Requests/Admin/ProposalServiceTemplateRequest.php`
- `app/Models/ProposalServiceTemplate.php`
- `docs/codex/prompts/05a5-proposal-cost-template-catalogue.md`
- `docs/codex/sessions/05a5-proposal-cost-template-catalogue-result.md`
- `lang/en/site.php`
- `lang/es/site.php`
- `resources/views/admin/proposal-templates/create.blade.php`
- `resources/views/admin/proposal-templates/edit.blade.php`
- `resources/views/admin/proposal-templates/index.blade.php`
- `resources/views/admin/proposal-templates/partials/form.blade.php`
- `resources/views/admin/proposals/partials/form.blade.php`
- `resources/views/layouts/panel.blade.php`
- `routes/web.php`
- `tests/Feature/AdminOperationsTest.php`
- `tests/Feature/ProposalAdministrationUsabilityTest.php`
- `tests/Feature/ProposalCostTemplateCatalogueTest.php`
