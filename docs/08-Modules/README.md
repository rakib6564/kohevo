# Active Modules

This section is the **as-built reference** for the active plugins under `plugins/`. Each page was reviewed against the module manifest, bootstrap, routes, administration pages, APIs, schema, integrations, and current documentation.

## Active catalogue

| Module | Version | Responsibility | Primary dependency or boundary | Reference |
|---|---:|---|---|---|
| Backups | 1.0.0 | Resumable database and uploads archives to Google Drive. | Super-admin, Google Drive, cron. | [Backups](backups.md) |
| Booking | 0.9.0 | Services, availability, appointments, payments, reminders, and calendars. | Stripe, Google Calendar, optional Booking+, Membership. | [Booking](booking.md) |
| Coaching | 0.6.0 | Membership-gated Body & Soul coaching program. | Membership, customer portal, cron. | [Coaching](coaching.md) |
| Forms | 0.7.5 | Public form builder, submissions, validation, webhooks, and PDFs. | Core rendering, mail, notifications, optional CAPTCHA/geo services. | [Forms](forms.md) |
| Licensing | 0.1.0 | Product, install, license, and signed check-in management. | Sodium, encrypted settings, external product clients. | [Licensing](licensing.md) |
| MCP Gateway | 0.1.0 | Tenant-scoped MCP JSON-RPC tools and AI assistant. | Core tool registry and installed-module handlers. | [MCP Gateway](mcp-gateway.md) |
| Media Library | 1.1.0 | Compatibility shim over the core media service and picker. | Core `Media`, uploads, usage tracking. | [Media Library](media-library.md) |
| Membership | 0.7.1 | Fixed-term plans, subscriptions, onboarding, wallet, and course access. | Stripe, Booking, customer identity. | [Membership](membership.md) |
| Multilang Translate | 1.3.0 | Harvested translation catalogue, editing, publishing, and live replacement. | Core I18n, crawler, optional LibreTranslate. | [Multilang Translate](multilang-translate.md) |
| Stripe Payment | 1.2.1 | Stripe Checkout, Payment Element, webhooks, charges, refunds, and Terminal wrappers. | Shop completion paths and Stripe. | [Stripe Payment](stripe-payment.md) |

## How to use a module page

Each page follows the same structure: scope and status, capabilities, data ownership, configuration, permissions, routes, hooks and integrations, operational lifecycle, security and limits, failure behavior, uninstall notes, and source map.

Use the [module documentation template](_template.md) when adding or revising an active module page.

The pages intentionally identify **declared-only**, **planned**, and **not found in code** integrations. A manifest’s `works_better_with` list is not proof that a runtime integration exists.

## Cross-module boundaries

Read [the dependency and contract map](../01-Architecture/dependency-map.md) before changing Booking, Membership, Stripe Payment, Shop, MCP, or any module that consumes shared identity, media, mail, or tenant services.

## Historical and planned documentation

The older module pages that described a proposed v3 architecture have been rewritten as current references where active code exists. Roadmap documents remain under [`docs/09-Roadmap`](../09-Roadmap/README.md) and must not be used as a description of deployed behavior.

## Verification standard

A module page is considered current only when it states the implementation version and has been compared with:

- `plugin.json` and the module bootstrap;
- install, migration, and uninstall behavior;
- public, customer, admin, API, and MCP entry points;
- permissions and tenant predicates;
- external integrations, cron, webhooks, and audit behavior; and
- known documentation or implementation discrepancies.
