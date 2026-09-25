# Kohevo Dependency and Contract Map

**Status:** As-built reference for the active module set  
**Verified:** 25 September 2026

Kohevo is a modular monolith. Modules share core services and communicate through hooks, filters, settings, and explicit integration handlers. A dependency listed in a manifest may be optional, declared-only, or implemented; the table below distinguishes those cases.

## Core boundaries

| Core service | Consumed by | Responsibility |
|---|---|---|
| Tenant context and `tenant_id` | All active modules | Isolates normal reads, writes, settings, and admin views. |
| Auth, permissions, CSRF | All admin modules; public mutation routes where applicable | Protects administration and state-changing requests. |
| Database and settings | All modules | Persistence, tenant settings, schema installation, and runtime reconciliation. |
| Hook/filter system | All active modules | Navigation, routes, dashboards, events, blocks, and cross-module extension points. |
| Media | Media Library, Forms, Membership, Coaching, MCP | Managed uploads, usage tracking, picker behavior, and safe deletion. |
| Mailer/notifications/audit | Booking, Forms, Coaching, Backups, MCP, Membership | Operational communication and traceability. |
| Cron | Backups, Booking, Coaching | Resumable work, reminders/sync, scheduled messages, and summaries. |

## Implemented cross-module relationships

| Relationship | Implementation status | Contract |
|---|---|---|
| Booking → Stripe Payment | Implemented | Booking uses Stripe Payment flows for hosted and embedded payment and expects Shop-specific completion where the payment plugin is used. |
| Stripe Payment → Shop | Implemented | Stripe completion directly calls Shop APIs and persists `stripepayment_sessions`; this is not a provider-neutral completion contract. |
| Membership → Stripe Payment | Implemented | Membership purchase creates Stripe Checkout and activates subscriptions through verified webhook or return processing. |
| Membership → Booking | Implemented | Booking filters can require an active course plan, completed profile, quota, and configured insurance. |
| Coaching → Membership | Implemented | Coaching enrollment requires an active subscription to `coaching.program_plan_id`. |
| Forms → Core Mailer/Notifications | Implemented | Form submissions persist first and perform email, notification, PDF, and webhook dispatch after response flush. |
| Forms → Content Builder | Implemented | Published forms can be selected as a content block and render through the public route. |
| Media Library → Core Media | Implemented | The active plugin is a compatibility shim; core `media_files` and `media_usage` own the active library. |
| MCP Gateway → module handlers | Implemented | Booking, Membership, Coaching, Forms, Stripe, and Multilang handlers register tools and scopes. |
| Booking → Google Calendar | Implemented | Provider OAuth, busy blocks, push/pull sync, webhook validation, and renewal are active. |
| Backups → Google Drive | Implemented | Super-admin cron/manual jobs create resumable archives and retain Drive files. |
| Multilang → LibreTranslate | Implemented optional | Per-string automatic translation uses a configurable LibreTranslate-compatible endpoint. |

## Declared or planned relationships that are not active contracts

The current code does not prove a generic `PaymentGateway`/`Money` contract, a universal `payment.succeeded` event, a Contact-owned booking customer model, a Booking+ dependency for Coaching, or a Forms platform webhook service. Those concepts remain architecture or roadmap material unless a module page explicitly identifies a concrete implementation.

## Change rule

When a module adds a cross-module dependency, update the module page, this map, the relevant API or event documentation, and the manifest. Include whether the relationship is required at boot, required only for a feature, or merely recommended.
