# Phase 2C - Repair Rich-Text Toolbar and Close Proposal Phase

Repair the restricted rich-text toolbar used by the proposal create and edit forms.

Approved Phase 2A/2B behavior must be preserved: compact created-date sorting, Client terminology, removed General proposal documents, six-section form order, reusable service-template append/copies, manual cost rows, proposal calculations, validation retention, public proposal behavior, PDF completeness, signature/totals layout, and ticket workflow behavior.

Primary defect:

- Toolbar controls rendered but did not apply formatting to selected content.
- Affected commands: bold, italic, bulleted list, numbered list, clear formatting.
- Affected fields: Detailed description and Scope.
- Affected pages: proposal create and proposal edit.

Required repair characteristics:

- Diagnose the source-verified root cause.
- Preserve/restore contenteditable selection for toolbar interaction.
- Keep the two editors independent.
- Use `type="button"` controls.
- Synchronize hidden inputs after input, paste, toolbar commands, validation reload, and submit.
- Keep the server sanitizer authoritative and restricted to `p`, `br`, `strong`, `em`, `ul`, `ol`, and `li`.
- Do not install a third-party editor dependency.
- Validate with browser interaction, automated tests, full regression, PDF output, and responsive screenshots.
- Create one local-only Phase 2 checkpoint commit named `feat: refine proposal editor templates and PDF output`.
