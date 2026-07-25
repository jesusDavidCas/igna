# Phase 1 Ticket Workflow Integrity Prompt

## Goal

Implement Phase 1 ticket workflow integrity for IGNA Studio:

1. Ticket-related emails use the language intended for each recipient.
2. Ticket stages advance only through explicit completion of the current active phase.
3. Client-visible ticket files appear immediately for the authorized client, while hidden and unrelated files remain inaccessible.

## Repository

- Root: `/Users/jesusdavid/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform`
- Branch: `fix/ticket-workflow-integrity`
- Expected starting commit: `6b49f5c`

## Constraints

- Use Graphify first, but do not regenerate Graphify.
- Do not modify `.env`, dependencies, migrations, production config, Hostinger, proposals, landing pages, blogs, or unrelated features.
- Do not run destructive database commands.
- Do not stage, commit, push, merge, or deploy.
- Accepted baseline artifacts include `.agents/`, `.graphifyignore`, `.playwright-cli/`, `AGENTS.md`, `docs/AI_ARCHITECTURE_GRAPH.md`, `docs/LANDING_UI_SYSTEM.md`, `docs/audits/`, `graphify-out/`, `graphify-query-smoke-test.txt`, `output/`, `resources/images/`, `skills-lock.json`, and `tests/Feature/FunctionalBoundaryTest.php`.
- Local `.env.backup*` files are known setup artifacts. Do not inspect them.

## Required Work

- Map ticket routes and workflows.
- Use Graphify evidence, then source-verify.
- Implement Phases 1A, 1B, and 1C in scoped application files.
- Add focused tests for locale, stage, and file-access behavior.
- Run focused tests after each phase, then full `php artisan test`, `npm run build`, `git diff --check`, `git status --short`, and `git diff --stat`.
- Verify local Laravel runtime and mail log with local-only data.
- Create `docs/codex/sessions/01-ticket-workflow-integrity-result.md`.

## Final Response Shape

Return the required headings:

- Goal result
- Starting branch, commit, and accepted baseline
- Local runtime status
- Graphify evidence used
- Complete ticket route map
- Phase 1A - Email-language result
- Phase 1B - Stage-transition result
- Phase 1C - File-visibility result
- Exact modification maps
- Focused tests
- Complete regression verification
- Local browser and mail verification
- Database impact
- Security findings
- Files changed
- Accepted baseline artifacts preserved
- Remaining risks
- Suggested path-specific Git commands

Finish with exactly one readiness line.
