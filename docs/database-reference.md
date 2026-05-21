# Database Reference

Last updated: 2026-05-21

This document explains the main database tables used by the IGNA Studio platform. MySQL is the production database. SQLite may be used locally for tests, but production data lives in Hostinger MySQL.

## Core Rule

The database is the source of truth for business records and file metadata.

Uploaded file binaries are not stored in MySQL. The database stores references, names, visibility, type, and provider information.

## Main Business Tables

### `users`

Stores platform accounts.

Important fields:

- `first_name`
- `last_name`
- `email`
- `phone`
- `preferred_language`
- `role`
- `is_active`
- `password`
- `signature_path`

Roles:

- `super_admin`
- `admin`
- `client`

Relationships:

- A user can be assigned to tickets as a client.
- A user can create/update blog posts.
- A user can sign proposals.

### `services`

Stores the configurable service catalog.

Important fields:

- `name`
- `slug`
- `code`
- `business_line`
- `service_type`
- `service_scope`
- `description`
- `deliverables_schema`
- `is_active`
- `sort_order`

Business lines:

- `digital`
- `engineering`

Relationships:

- A service has many `service_stages`.
- A service has many `service_deliverables`.
- A service has many tickets.

### `service_stages`

Stores configurable workflow stages per service.

Important fields:

- `service_id`
- `name`
- `code`
- `description`
- `sort_order`
- `is_active`
- `is_client_visible`

Usage:

When a ticket is created, active stages for the selected service are copied into `ticket_stage_events`.

### `service_deliverables`

Stores expected deliverables per service.

Important fields:

- `service_id`
- `name`
- `description`
- `sort_order`
- `is_active`
- `is_client_visible_by_default`

Usage:

When a ticket is created, active deliverables for the selected service are copied into `ticket_deliverables`.

### `tickets`

Stores public requests and tracked projects.

Important fields:

- `ticket_code`
- `service_id`
- `client_user_id`
- `current_service_stage_id`
- `first_name`
- `last_name`
- `email`
- `phone`
- `project_name`
- `project_location`
- `preferred_language`
- `project_description`
- `target_date`
- `status`
- `submitted_at`
- `google_drive_folder_id`
- `google_drive_folder_url`

Status values are represented by `App\Enums\TicketStatus`.

### `ticket_stage_events`

Stores the stage timeline for a ticket.

Important fields:

- `ticket_id`
- `service_stage_id`
- `status`
- `entered_at`
- `completed_at`
- `changed_by_user_id`
- `is_client_visible`
- `notes`

Status values are represented by `App\Enums\StageEventStatus`.

Important behavior:

- The current stage and completed stages are separate concepts.
- Completing a stage creates a timestamp.
- Reopening/correcting a stage must leave traceability instead of deleting history.

### `ticket_deliverables`

Stores deliverable slots copied into each ticket.

Important fields:

- `ticket_id`
- `service_deliverable_id`
- `name`
- `description`
- `status`
- `sort_order`

Usage:

Ticket files can be linked to a ticket deliverable, so admins upload files to a specific expected output rather than one generic file list.

### `ticket_files`

Stores file metadata.

Important fields:

- `ticket_id`
- `ticket_deliverable_id`
- `title`
- `original_name`
- `stored_name`
- `storage_provider`
- `storage_disk`
- `storage_path`
- `google_drive_file_id`
- `google_drive_url`
- `mime_type`
- `size_bytes`
- `is_client_visible`
- `delivery_type`
- `visibility`
- `uploaded_by_user_id`

Storage providers:

- `local_stub`: current private local storage fallback.
- `google_drive`: future/optional Google Drive provider.

### `blog_posts`

Stores blog content.

Important fields:

- `title`
- `slug`
- `summary`
- `body_html`
- `status`
- `published_at`
- `seo_keywords`
- `header_image_path`
- `created_by_user_id`
- `updated_by_user_id`
- `deleted_at`

Public rendering uses sanitized HTML.

### `settings`

Stores editable platform settings.

Important fields:

- `group`
- `key`
- `value`
- `type`
- `is_public`

Used for:

- Company name
- Support email
- Logo text/path
- Favicon path
- Storage backend marker

### `team_members`

Stores public team profiles.

Important fields:

- `slug`
- `name`
- `role`
- `short_description`
- `bio`
- `expertise`
- `photo_path`
- `sort_order`
- `is_active`

### `team_credentials`

Stores credential metadata and private file references.

Important fields:

- `team_member_id`
- `title`
- `institution`
- `issue_date`
- `file_path`
- `mime_type`
- `is_public`
- `views_count`
- `sort_order`

Original files are private. Public access uses signed routes and protected previews.

### `team_credential_views`

Stores credential view events.

Important fields:

- `team_credential_id`
- `ip_address`
- `user_agent`
- `viewed_at`

Used for basic auditability.

### `proposals`

Stores quote/proposal headers and financial summary.

Important fields:

- `proposal_number`
- `client_user_id`
- `client_name`
- `client_email`
- `client_phone`
- `title`
- `subject`
- `description`
- `scope`
- `timeline_months`
- `timeline_weeks`
- `payment_schedule`
- `tax_rate`
- `subtotal`
- `tax_total`
- `total`
- `status`
- `signer_user_id`
- `validity_days`
- `valid_until`

### `proposal_items`

Stores itemized cost rows.

Important fields:

- `proposal_id`
- `category_code`
- `category_name`
- `item_code`
- `description`
- `unit`
- `quantity`
- `unit_value`
- `total_value`
- `sort_order`

The proposal form calculates:

```text
total_value = quantity * unit_value
subtotal = sum(total_value)
tax_total = subtotal * tax_rate
total = subtotal + tax_total
```

## Laravel System Tables

Laravel also uses:

- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `sessions`
- `password_reset_tokens`

Current production recommendation:

```env
QUEUE_CONNECTION=sync
```

Use actual queue workers only if you configure a supervised process.

## Data Lifecycle Summary

### New Request

```text
services
  -> ticket
  -> ticket_stage_events
  -> ticket_deliverables
  -> email notifications
```

### Admin Uploads File

```text
ticket
  -> optional ticket_deliverable
  -> ticket_files metadata
  -> private local storage or Google Drive
  -> optional client notification if made visible
```

### Client Tracks Project

```text
ticket_code + email
  -> ticket
  -> visible stage events
  -> visible files
  -> signed download route
```

### Proposal Created

```text
proposal
  -> proposal_items
  -> payment_schedule JSON
  -> signer
  -> PDF
  -> signed public view
  -> WhatsApp link
```

## Backup Priorities

Back up these first:

- MySQL database.
- `storage/app/private`
- `storage/app/public`
- `.env` separately and securely.

Do not rely on GitHub for uploads or production data.
