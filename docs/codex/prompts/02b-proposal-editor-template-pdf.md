# Phase 2B - Proposal Editor, Reusable Templates, and PDF Resilience

## Objective

Refine the uncommitted Phase 2A proposal administration work and complete the next proposal-management improvements.

## Required Outcomes

1. Replace the proposal-list sorting control with a compact accessible created-date sort button using up/down arrows.
2. Remove the General proposal documents feature completely.
3. Add restricted rich-text editing to Detailed description and Scope.
4. Reorder proposal sections to six sections.
5. Allow service templates to be appended repeatedly, including several copies of the same template.
6. Improve proposal PDF completeness and visual balance.
7. Preserve existing proposal calculations, ticket behavior, authorization, and public-proposal functionality.

## Constraints

- Do not modify ticket workflow or ticket-file behavior.
- Do not add public proposal decisions, comments, or client proposal uploads.
- Do not update dependencies.
- Do not modify `.env`.
- Do not stage, commit, push, merge, deploy, reset, clean, stash, or run destructive database commands.
- Do not regenerate Graphify.

## Verification

- Focused proposal tests first.
- Full `php artisan test`.
- `npm run build`.
- `git diff --check`.
- `php artisan migrate:status`.
- Browser screenshots under `output/ui-review/phase-2b/`.
- PDF QA artifacts under `output/pdf-review/phase-2b/`.
