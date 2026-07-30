# Phase 5A.5 - Proposal Cost-Template Catalogue

## Objective

Create a dedicated administrative catalogue for reusable proposal cost templates, preserving the existing proposal editor workflow while separating proposal pricing templates from public service administration.

## Required Scope

- Verify and checkpoint Phase 5A.4 before starting.
- Work from the `feat/proposal-cost-template-catalogue` branch.
- Reuse the existing `proposal_service_templates` and `proposal_service_template_items` architecture where possible.
- Add admin catalogue screens for listing, creating, editing, activating/deactivating, and duplicating templates.
- Keep active templates available in the proposal editor.
- Hide inactive templates from the proposal editor while keeping them visible and editable in the catalogue.
- Preserve historical proposal immutability: inserted template rows must become saved proposal items, not live references.
- Keep proposal cost-template administration independent from public service taxonomy and service administration.
- Do not change proposal calculations, public-service behavior, ticket workflow, dependencies, production configuration, or deployment state.

## Verification Requirements

- Focused tests for catalogue CRUD, authorization, active/inactive behavior, duplication, editor integration, historical proposal immutability, public-service independence, validation retention, and PDF rendering.
- Proposal, service, and public selector regression coverage.
- Full application test suite.
- Composer and npm audits.
- Frontend production build.
- Git diff check.
- Migration status review.
- Browser validation where local authentication is available.

## Final State

Leave Phase 5A.5 source changes unstaged and uncommitted for integrated human review.
