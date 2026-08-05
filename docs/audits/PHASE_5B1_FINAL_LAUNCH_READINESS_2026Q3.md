# Phase 5B.1 Final Launch Readiness - 2026 Q3

## Scope

This report closes the local Phase 5B.1 release-candidate source pass for:

- Proposal-to-project conversion.
- Projects / Proyectos terminology.
- Guzzle 7.15.2 security remediation in `composer.lock`.
- Favicon upload and delivery repair.
- Project creation-date display and sorting.
- Dashboard Recent Projects wording and dates.
- Guarded launch-data reset command.
- Release packaging and deployment preparation.

No production access, deployment, push, or merge occurred during this pass.

## Favicon Root Cause

Uploaded favicon settings stored a trusted `brand_favicon_path`, but layouts rendered the URL through `Storage::disk('public')->url($path)`. That path depends on `public/storage`, which is not linked in the Hostinger public bridge and can also fail locally when the storage symlink is absent or stale.

## Favicon Remediation

- Added public route `GET /brand/favicon`, route name `brand.favicon`.
- Added `App\Http\Controllers\Public\BrandFaviconController`.
- Extended `App\Support\Settings\BrandSettings` to produce versioned routed favicon URLs.
- The route reads only the configured `brand_favicon_path`, requires it to stay under `branding/`, rejects traversal implicitly by never accepting path input, validates decodability, and falls back safely.
- Responses include image MIME, `Cache-Control`, `ETag`, and `X-Content-Type-Options: nosniff`.
- Shared `resources/views/components/favicon-links.blade.php` is included by public, panel, and the complete upload-size error layout.
- Static fallback assets are tracked in `public/`.

## Settings UX

- Current favicon preview is visible.
- Browser-icon replacement remains available.
- Restore default favicon is available.
- Raw `brand_favicon_path` and `brand_logo_path` fields are hidden from the generic settings editor.
- Logo-image data remains backward-compatible in storage and settings, but the duplicate upload UI was removed from the primary Settings page.
- Upload validation requires PNG/ICO, real MIME, decodable image data, 512 KB maximum, 16x16 minimum, 1024x1024 maximum, and square or near-square dimensions.

## Project Timeline UX

- Project index shows a localized creation date using existing `tickets.created_at`.
- Project detail shows creation date near general project information.
- Dashboard Recent Projects shows creation dates.
- Project index supports safe server-side sorting with `sort=created_at&direction=asc|desc`.
- Invalid sort or direction values fall back to newest-first ordering.
- No duplicate timestamp column was added.
- Date filtering was not implemented because the proposal index currently provides created-date sorting only, not a date-range filter UI.

## Launch Reset

- Added `igna:launch-reset`.
- Dry-run mode is the default and deletes nothing.
- Force mode requires `--force --confirm=RESET-LAUNCH-DATA`.
- The command refuses to run if preserved superadministrator `jesus.castaneda@ignastudio.com` is missing or is not a superadministrator.
- It deletes projects/tickets, project files, stage events/audits, proposals, proposal items, proposal/project links, non-superadministrator users, and relevant session rows.
- It preserves services, service stages, service deliverables, proposal templates and template items, settings, branding/favicon settings, team, blog content, migrations, and launch master data.
- Preserved blog ownership is reassigned to the preserved superadministrator before deleting users.
- Production reset remains a separate future operation after deployment validation, database backup, file backup, dry-run review, and explicit human approval.

## Verification

- `php artisan test --filter=FaviconDeliveryTest`: 7 tests, 36 assertions.
- `php artisan test --filter=ProjectTimelineTest`: 5 tests, 27 assertions.
- `php artisan test --filter=LaunchDataResetTest`: 3 tests, 31 assertions.
- `php artisan test --filter=ProposalToProjectBridgeTest`: 6 tests, 82 assertions.
- `php artisan test`: 168 tests, 1447 assertions.
- `composer validate`: passed.
- `composer audit --locked`: no security vulnerability advisories found.
- `composer check-platform-reqs`: passed on local PHP 8.5.8.
- `npm audit --omit=dev`: 0 vulnerabilities.
- `npm audit`: development-only advisories remain in PostCSS, Vite, and shell-quote via concurrently.
- `npm run build`: passed with the existing Node `module.register()` deprecation warning.
- `git diff --check`: passed at verification time.
- `php artisan route:list --except-vendor`: compiled.
- Disposable SQLite migration apply, rollback of `2026_08_04_000100_add_proposal_project_bridge`, and reapply passed.

## Browser and HTTP Evidence

- Homepage and login contain routed favicon links.
- `GET /brand/favicon` returned HTTP 200 and `image/png` for the configured local favicon.
- `GET /favicon.ico` returned HTTP 200 and `image/vnd.microsoft.icon`.
- In-app browser opened the routed favicon directly as an image page.
- In-app browser authenticated with disposable local QA data and verified Projects creation date, ascending sort URL, project detail date, and Spanish Recent Projects dashboard wording.
- Evidence files remain untracked under `output/ui-review/phase-5b1-final/`.

## Graphify

- Refresh command: `uv tool run --from graphifyy graphify update ..`
- Result: 2290 nodes, 3793 edges, 280 communities.
- Known non-blocking warning: `skills-lock.json` produced zero nodes.
- Query smoke test found the favicon resolver, Settings UX, project sorting/date surfaces, proposal bridge, reset command/service, and tests.

## Readiness Classification

- Proposal-to-project bridge: READY.
- Guzzle security remediation: READY.
- Favicon delivery: READY.
- Project timeline UX: READY.
- Dashboard Recent Projects: READY.
- Launch reset tooling: READY WITH MANUAL PRODUCTION APPROVAL REQUIRED.
- Composer production security: READY.
- npm production security: READY.
- Full npm development audit: DEFERRED NON-BLOCKING FOR PRODUCTION.
- Production deployment: READY WITH HUMAN PUSH AND DEPLOYMENT APPROVAL REQUIRED.
