# Phase 1D Prompt

Implement Phase 1D ticket layout and secure client document exchange for IGNA Studio.

Required outcomes:

- Reorganize the administrator ticket page so Assign client is followed by General project files, then Stage completion.
- Move Deliverables below the project timeline and render them as a wide horizontal responsive section.
- Allow restricted client-submitted documents from the initial public request, authenticated client ticket page, and validated public tracking page.
- Preserve strict separation between proposal acceptance, payment evidence, general project files, client-submitted documents, and deliverable files.
- Enforce 2 MB PDF/JPG/JPEG/PNG-only validation for all client-originated uploads.
- Preserve the 20 MB administrator upload policy.
- Store client documents privately, pending review, with upload source/category/context metadata.
- Use signed, expiring public tracking upload URLs bound to ticket and email hash, with a conservative rate limiter.
- Do not implement proposal accept/request changes/reject decisions in this phase.
- Do not stage, commit, push, merge, deploy, update dependencies, modify `.env`, or access production.
