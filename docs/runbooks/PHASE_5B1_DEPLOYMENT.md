# Phase 5B.1 Deployment Runbook

## Purpose

Deploy Phase 5B.1 after human approval. Do not combine deployment with launch-data reset.

## Production Reference

- App root: `/home/u935649387/apps/igna-studio`
- Public bridge: `/home/u935649387/domains/ignastudio.com/public_html/igna-app`
- Current production baseline: `release/phase-5a-2026q3` at `e79d6781591f5617f92e6adde55df16b2abb59e7`
- Required future release branch: `release/phase-5b1-2026q3`

## Order

1. Human approves local QA and source diff.
2. Push the release branch.
3. Upload the public artifact from `output/releases/phase-5b1/`.
4. Verify artifact SHA-256.
5. Create hPanel file and database backup.
6. Create server-side private backup.
7. Verify clean production worktree.
8. Verify current production commit.
9. Enter maintenance mode.
10. Check out the exact Phase 5B.1 release commit.
11. Run `composer install` from lockfile, never `composer update`.
12. Run `composer audit --locked`.
13. Apply additive migrations.
14. Clear and rebuild Laravel caches.
15. Install built assets and favicon files into the public bridge.
16. Preserve `.env` and Ghostscript configuration.
17. Restart queues if configured.
18. Leave maintenance mode.
19. Smoke test homepage, login, `/brand/favicon`, `/favicon.ico`, Projects index sorting, project detail creation date, dashboard Recent Projects, proposal conversion, and public tracking.
20. Inspect Laravel logs.
21. Record deployment.

## Public Bridge Assets

Install only into:

`/home/u935649387/domains/ignastudio.com/public_html/igna-app`

Never synchronize or delete:

`/home/u935649387/domains/ignastudio.com/public_html`

Required public assets:

- `build/`
- `favicon.ico`
- `favicon-16x16.png`
- `favicon-32x32.png`
- `apple-touch-icon.png`
- `android-chrome-192x192.png`
- `android-chrome-512x512.png`
- `site.webmanifest`

## Post-Deployment Checks

- Homepage HTTP 200.
- Login HTTP 200.
- `/brand/favicon` HTTP 200 with image MIME.
- `/favicon.ico` HTTP 200 with icon MIME.
- Fresh browser profile shows the favicon.
- Projects index shows creation dates.
- Created-at sort toggles newest/oldest.
- Project detail shows creation date.
- Dashboard says Recent Projects / Proyectos recientes.
- Proposal-to-project conversion still creates one linked project.
- Launch reset command dry run only; do not force reset during deployment.
