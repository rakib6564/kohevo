# Forms

**Status:** As built
**Verified version:** `0.7.5`
**Owner:** `plugins/forms`

## Purpose

Forms provides a tenant-scoped form builder and public submission system for contact, intake, quote, questionnaire, and agreement workflows. It includes a visual editor, legacy pipe DSL, advanced field logic, submissions inbox, contacts roll-up, email, PDFs, webhooks, anti-spam, and MCP tools.

## Public and admin surfaces

Published forms render at `/forms/<slug>`. The route supports standalone, iframe embed (`?embed=1`), bare inline embed (`?embed=1&chrome=0`), thanks, and signed PDF download. Forms can also be embedded through the Content Builder block.

Admin pages manage definitions, builder settings, submissions, workflow status, derived contacts, spam logs, CSV exports, and PDF downloads. Submission statuses are `new`, `in_progress`, and `done`; read state is tracked separately.

## Fields and submission lifecycle

Supported fields include text, email, phone, international phone, URL, number, date, time, address, textarea, select, radio, checkbox, checkbox groups, range, rating, file, signature, heading, hidden, calculated, step, and disclaimer. Conditional visibility, multi-step forms, choice cards, server-side formula recalculation, signatures, file uploads, and branded PDFs are active features.

A submission passes through honeypot, CSRF, spam guard, field validation, file/signature handling, persistence, and contact roll-up. The response redirects quickly; PDF, emails, notifications, webhook delivery, audit, and `forms_submitted` dispatch run after response flush. Email success/error fields are recorded on the submission.

## Data and permissions

The module owns `forms_definitions`, `forms_submissions`, `forms_webhooks`, `forms_webhook_log`, `forms_spam_log`, and runtime-created `forms_contacts`. Form and submission settings are stored in JSON. Admin permissions are `forms.view`, `forms.manage`, and `forms.export`; MCP scopes are `forms.read` and `forms.write`.

## Security and operations

Spam controls include honeypot, IP/CIDR blocks, timing trap, content rules, country allow/block, rate limiting, and Turnstile/reCAPTCHA/hCaptcha. Webhooks use HMAC-SHA256, SSRF/DNS checks, no redirect following, bounded timeouts, and delivery logs. Public PDF links use expiring signed tokens. CSV export neutralizes formula injection.

The default rate limit in the active guard is five submissions per IP per 60 seconds unless settings change it. CAPTCHA provider outages fail open; rejected tokens fail closed. Embed mode intentionally permits broad framing. The runtime schema reconciles columns and tables not fully represented by `install.sql`.

## Source map and known gaps

See `Forms.php`, `FormsAPI.php`, `FormsMcpHandler.php`, `public/router.php`, `admin/`, `lib/FormsSpamGuard.php`, `lib/FormsPdf.php`, `install.sql`, and `uninstall.sql`.

Older documentation described planned platform webhooks, queued notifications, Contact linkage, and fewer field types. The active plugin owns webhook delivery, uses the `forms_submitted` hook, has a forms-specific contacts roll-up, and performs best-effort post-response dispatch. `forms_contacts` is not removed by the current uninstall SQL.
