# Public Request Anti-Spam Rollout

## Scope

This runbook covers the public `POST /request` guard. It does not change DNS,
require Cloudflare proxying, add a migration, or introduce Redis. The feature
is deployed disabled and only becomes active when `REQUEST_ANTISPAM_ENABLED`
is set to `true` in the production environment.

## Pre-activation requirements

1. Create a real Cloudflare Turnstile widget for `ignastudio.com` with action
   `service_request`. Do not use Cloudflare test credentials in production.
2. Store `TURNSTILE_SECRET_KEY` only in Hostinger's private environment file.
   Never add it to Git, a browser response, an email, or an operational log.
3. Set `TURNSTILE_SITE_KEY`, `TURNSTILE_EXPECTED_HOSTNAME=ignastudio.com`,
   `TURNSTILE_EXPECTED_ACTION=service_request`, and the real secret.
4. Confirm Hostinger's proxy topology. Laravel must receive the actual client
   IP through `Request::ip()` before relying on per-IP limits. Do not trust
   arbitrary forwarded headers or configure wildcard trusted proxies.
5. Obtain business/legal approval for privacy-notice wording that explains the
   transfer of the client IP and challenge response to Cloudflare solely for
   fraud and abuse prevention.
6. If a hosting-layer CSP is enabled, allow only the Turnstile origins needed
   by the vendor: `https://challenges.cloudflare.com` for `script-src` and
   `frame-src`. Do not weaken CSP with wildcard sources.

## Activation

1. Deploy the reviewed code while `REQUEST_ANTISPAM_ENABLED=false`.
2. Run `php artisan config:cache` using the production environment and confirm
   that the public homepage loads without exposing the secret key.
3. Populate the required production-only values and set
   `REQUEST_ANTISPAM_ENABLED=true`.
4. Run `php artisan config:cache` again. Submit one real request from the
   production hostname and verify a ticket, workflow, and notifications are
   created only after a successful challenge.
5. Confirm rejected requests receive a safe localized message and create no
   ticket, workflow event, uploaded document, notification, or tracking code.

The initial limits are five requests per minute and twenty per hour per
verified client IP. Shared offices or mobile carrier NATs can share that limit;
review `request_antispam.rate_limited` event volume before lowering it.

## Operations and privacy

Events use `request_antispam.*` names and an application-keyed client
identifier. They must never include a Turnstile token, secret, raw form body,
uploaded-file metadata, project description, or full email address. Aggregate
event counts can be reviewed in the configured Laravel log destination.

## Rollback

If Turnstile is unavailable or creates a verified usability incident, set
`REQUEST_ANTISPAM_ENABLED=false`, run `php artisan config:cache`, and monitor
the existing route limiter. This is a temporary operational rollback, not a
recommended steady state. Do not remove validation, expose a bypass URL, or
replace trusted-proxy configuration without infrastructure evidence.
