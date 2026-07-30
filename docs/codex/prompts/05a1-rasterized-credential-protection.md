# Phase 5A.1 - Rasterized Credential Protection

## Objective

Implement stronger protection for downloadable IGNA Studio team-member credentials so the public credential route no longer serves or dynamically rebuilds a PDF that preserves the original editable text/content layer beneath a removable watermark.

## Required Behavior

- Store the uploaded original credential privately.
- Generate a separate private protected derivative.
- Render every PDF page as an image before assembling the protected PDF.
- Burn the IGNA Studio watermark into the raster page pixels.
- Support the credential formats already accepted by the application: PDF, JPG/JPEG, PNG, and WEBP.
- Return only the protected derivative from the signed credential download route.
- Fail closed when the protected derivative is missing or generation fails.
- Preserve existing authorization boundaries and signed public credential links.
- Add focused tests, local synthetic validation evidence, and production compatibility documentation.

## Constraints

- Do not push, merge, deploy, or access production.
- Do not install or upgrade Composer or npm dependencies.
- Do not broaden unrelated product behavior.
- Do not expose real credential data, private paths, secrets, cookies, tokens, or signed URLs in documentation.
- Leave implementation changes unstaged for human review.

## Required Validation

- Run focused credential tests.
- Run the full Laravel suite, Composer audit, npm production audit, frontend build, diff checks, and migration status.
- Apply the additive migration locally, roll it back, and reapply it.
- Produce local-only synthetic evidence under `output/credential-review/phase-5a1/`.
