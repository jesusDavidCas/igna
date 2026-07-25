# Phase 2A - Proposal Administration and Usability

## Source Prompt

Implement Phase 2A after locally closing the approved Phase 1 ticket workflow work.

## Objective A

- Verify the Phase 1 worktree on `fix/ticket-workflow-integrity`.
- Stage only explicit Phase 1 implementation, migration, test, and documentation paths.
- Create the local-only checkpoint commit:
  - `feat: harden ticket workflow and document exchange`
- Do not push.

## Objective B

- Create `release/igna-workflow-proposals-2026q3` at the Phase 1 checkpoint.
- Create and switch to `feat/proposal-admin-information-architecture`.
- Implement Phase 2A without staging or committing the Phase 2 changes.

## Phase 2A Outcomes

1. Reorganize the proposal create/edit interface into an operational sequence.
2. Add or improve a General proposal documents area near the top of proposal administration.
3. Show proposal creation date in the proposal list and support chronological sorting.
4. Replace visible Prospect terminology with Client terminology without destructive schema renames.
5. Improve validation feedback with summary, inline errors, retained input, and first-error navigation.

## Scope Boundaries

Allowed work:

- Administrator proposal index, create, edit, and directly related detail pages.
- Proposal controllers, requests, queries, sorting, translations, and tests.
- Narrow proposal-document model, controller, migration, private storage, and authorized downloads when no coherent existing attachment model exists.
- Phase 2A documentation.

Forbidden work:

- Public proposal accept/reject workflow.
- Proposal PDF redesign or overflow work.
- Ticket workflow redesign.
- Client proposal uploads.
- Dependency updates.
- Environment changes.
- Production, deploy, push, merge, rebase, reset, clean, stash, or Phase 2 staging/commit.

## Verification Required

- Focused Phase 2A tests.
- Full `php artisan test`.
- `npm run build`.
- `git diff --check`.
- `git status --short`.
- `git diff --stat`.
- Local migration apply, rollback, reapply when a migration is added.
- Browser validation at 1440x900, 1280x800, 1024x768, 768x900, and 390x844.
- Screenshots saved under `output/ui-review/phase-2a/` and not committed.
