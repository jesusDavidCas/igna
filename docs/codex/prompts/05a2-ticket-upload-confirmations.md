# Phase 5A.2 - Ticket Document Upload Confirmations

## Objective

Improve ticket document upload confirmations after Phase 5A.1 credential protection:

- Show clearer localized success messages for public tracking uploads and authenticated client uploads.
- Send one localized confirmation email to the ticket client or validated public tracking uploader.
- Send separate localized admin update emails to active responsible administrators.
- Dispatch notifications only after the upload database record is persisted.
- Preserve uploaded documents when notification delivery fails.

## Scope

Allowed:

- Public tracking document upload route.
- Authenticated client document upload route.
- Localized flash messages.
- Domain event and listener.
- Notification service recipient resolution.
- Mailables, email Blade templates, tests, and local documentation.

Excluded:

- Credential protection or watermark redesign.
- Team-member administration.
- Service administration.
- Proposal behavior.
- Ticket stage lifecycle changes.
- Document review lifecycle changes.
- Public tracking authentication redesign.
- Notification center redesign.
- Production changes.
- Dependency upgrades.

## Validation Expectations

- Existing ticket file persistence, storage, visibility, review status, and stage behavior remain unchanged.
- Client and admin email delivery is localized per recipient.
- Admin recipients are active admins/super admins plus the configured support recipient, deduplicated by email.
- Email failures are reported but do not roll back or delete the uploaded file.
- No schema change is introduced.
