# Kohevo

**A modular business platform for building customer-facing digital operations.**

Kohevo brings websites, customer portals, scheduling, memberships, forms, payments, media, communication, automation, backups, and AI-assisted administration into one extensible platform.

It is designed for organizations that need more than a marketing website but do not want to assemble and maintain a disconnected collection of SaaS tools. Kohevo provides a shared foundation for identity, tenant isolation, permissions, rendering, data, payments, media, audit logging, and scheduled work. Business capabilities are added as modules and composed into a product that fits the organization.

> **Kohevo is not a single-purpose booking, CRM, or commerce application. It is a platform for assembling those capabilities into a coherent business system.**

## What is Kohevo?

Kohevo is a **server-rendered, modular monolith** built for practical business software. It combines the operational simplicity of a traditional PHP application with the extensibility of a plugin platform.

A Kohevo installation can be used as:

- a public website and customer portal;
- a scheduling and appointment platform;
- a membership, subscription, or course-access system;
- a form and intake workflow;
- a coaching or client-program application;
- a payment-enabled customer operation;
- a multilingual and media-managed digital service; or
- a white-label foundation for a specialized vertical product.

The product is intentionally modular. An organization can start with a website and forms, add booking and payments later, then add memberships, coaching, or AI tools without replacing its identity and administration foundation.

## Product value

### One operational system instead of disconnected tools

Customer identity, portal access, scheduling, forms, payments, communication, and content can share the same tenant and administrative context. This reduces duplicate records, repeated logins, manual exports, and fragile integrations.

### A foundation that can be adapted to a business

Kohevo does not force every organization into the same workflow. Modules provide reusable capabilities while configuration, permissions, content, forms, services, plans, and templates shape the final product.

### Practical deployment

The platform uses PHP, MySQL or MariaDB, server-rendered HTML, progressive enhancement, and a no-build deployment model. It is suitable for upload-and-run shared hosting and can be operated on larger infrastructure when the deployment requires it.

### Extensible without a rewrite

The core provides hooks, filters, module lifecycle, tenant context, permissions, settings, audit logging, media, mail, public routes, customer routes, and scheduled execution. Modules can extend the platform through these boundaries instead of creating parallel foundations.

## Who is Kohevo for?

Kohevo is intended for:

- **Service businesses** that need online scheduling, intake, payments, reminders, and customer self-service.
- **Practitioners and consultancies** that manage client programs, forms, memberships, progress, and communication.
- **Membership organizations** that sell access, subscriptions, courses, events, or recurring services.
- **Education and coaching providers** that combine enrollment, structured content, progress tracking, communication, and summaries.
- **Studios and appointment-led businesses** that coordinate providers, capacity, resources, availability, and customer records.
- **Agencies and white-label product teams** that need a reusable business application foundation for multiple clients or tenants.
- **Small and growing businesses** that need a focused operational system without the cost and complexity of a large enterprise suite.
- **Developers and product teams** that want to build vertical applications on a stable PHP platform rather than start with authentication, tenancy, administration, and integrations from zero.

## Industry and market use cases

Kohevo is particularly well suited to industries where customer relationships, scheduled services, structured data, and recurring engagement meet.

| Industry or segment | Example Kohevo product |
|---|---|
| Health and wellness | Practitioner website, assessment forms, appointment booking, memberships, and client programs. |
| Nutrition and coaching | Intake, subscription access, goals, diaries, progress views, recipes, chat, and summaries. |
| Therapy and wellbeing services | Confidential intake, appointment workflows, payment collection, reminders, and client communication. |
| Fitness, yoga, and studios | Class or service scheduling, capacity, memberships, attendance-related access, and customer portals. |
| Education and training | Course plans, enrollment, structured content, forms, progress tracking, and recurring communication. |
| Professional services | Consultation booking, lead forms, deposits, invoices, client portals, and follow-up workflows. |
| Agencies and consultants | White-label client portals, project-specific forms, service catalogues, subscriptions, and communication. |
| Associations and communities | Member records, plans, digital cards, access rules, notifications, and multilingual experiences. |
| Local service businesses | Service catalogue, provider availability, online booking, payments, reminders, and customer history. |
| Specialized SaaS products | A tenant-aware foundation for a focused vertical application with custom modules. |

These are product patterns, not restrictions. The active modules are composable and can support other appointment-led, membership-led, form-led, or customer-portal businesses.

## Core product capabilities

### Customer and identity foundation

Kohevo provides customer registration, authentication, verification, password recovery, profiles, customer portal navigation, tenant context, administrative users, roles, permissions, and audit history.

### Website and content experience

The platform supports server-rendered pages, themes, design tokens, reusable components, content blocks, public routes, customer portal surfaces, media, SEO-oriented rendering, and multilingual presentation.

### Scheduling and booking

The Booking module manages services, providers, hours, breaks, overrides, locations, resources, availability, appointments, recurrence, custom fields, customer history, reminders, follow-ups, coupons, gift cards, invoices, messages, Stripe payments, Google Calendar synchronization, and self-service manage links.

### Membership and access

Membership supports fixed-term plans, insurance and course plans, Stripe or manual activation, onboarding, profile data, QR member cards, wallet balances, subscription states, grace periods, and Booking eligibility rules.

### Forms and intake

Forms provides a visual form builder, public forms, embedded forms, conditional logic, multi-step workflows, calculated fields, file uploads, signatures, submissions, spam protection, PDFs, email notifications, webhooks, CSV export, and submission workflow statuses.

### Coaching and client programs

Coaching adds a membership-gated program surface with profiles, goals, check-ins, diary entries, hydration, activity, charts, chat, motivation, recipes, meal structures, shopping lists, and end-of-program summaries.

### Payments

Stripe Payment provides hosted Checkout, embedded Payment Element, verified webhooks, Shop completion paths, charge ledger, refunds, payment-method configuration, and Stripe Terminal server-side wrappers.

### Media and localization

The core Media service manages uploads, metadata, usage, import, safe deletion, images, documents, and a reusable picker. Multilang Translate provides language administration, on-demand string harvesting, translation drafts, publishing, CSV workflows, and a customer/admin/public language switcher.

### Automation, recovery, and AI assistance

Backups creates resumable database and uploads archives in Google Drive. MCP Gateway exposes scoped JSON-RPC tools and separate admin/customer AI assistant surfaces. Cron-based processing supports reminders, scheduled messages, synchronization, backup progress, and program summaries.

## How Kohevo is structured

Kohevo has three practical layers:

1. **Core platform:** identity, tenancy, authentication, permissions, database, settings, rendering, media, mail, audit logging, hooks, routing, and scheduled execution.
2. **Business modules:** Booking, Membership, Forms, Coaching, Stripe Payment, Media Library, Multilang Translate, Backups, Licensing, and MCP Gateway.
3. **Product configuration:** services, providers, plans, forms, templates, permissions, language settings, payment settings, content, and customer workflows.

The result is a single deployable product with explicit internal boundaries. A business can use a small subset of modules or assemble a broader operational platform.

## Deployment model

Kohevo is built for:

- PHP 8.1+;
- MySQL or MariaDB;
- Apache with rewrite support, or equivalent web-server configuration;
- common PHP extensions such as PDO MySQL, mbstring, zip, openssl, fileinfo, curl, and GD; and
- shared hosting, VPS, or managed PHP infrastructure.

The default deployment has no Node or server-side build requirement. Assets are served and composed by the application. Cron provides scheduled processing for modules that require background progress.

## Active modules

| Module | Responsibility | Reference |
|---|---|---|
| Backups | Database and uploads archives with Google Drive retention. | [Module reference](docs/08-Modules/backups.md) |
| Booking | Services, availability, appointments, payments, reminders, and calendars. | [Module reference](docs/08-Modules/booking.md) |
| Coaching | Membership-gated client program and progress workflows. | [Module reference](docs/08-Modules/coaching.md) |
| Forms | Public form builder, intake, submissions, PDFs, and webhooks. | [Module reference](docs/08-Modules/forms.md) |
| Licensing | Product licenses, installs, signed check-ins, and entitlements. | [Module reference](docs/08-Modules/licensing.md) |
| MCP Gateway | Scoped AI and automation tools through MCP JSON-RPC. | [Module reference](docs/08-Modules/mcp-gateway.md) |
| Media Library | Core media compatibility layer and reusable picker. | [Module reference](docs/08-Modules/media-library.md) |
| Membership | Plans, subscriptions, onboarding, wallets, and access rules. | [Module reference](docs/08-Modules/membership.md) |
| Multilang Translate | Translation catalogue, publishing, and language switcher. | [Module reference](docs/08-Modules/multilang-translate.md) |
| Stripe Payment | Checkout, Payment Element, webhooks, charges, refunds, and Terminal wrappers. | [Module reference](docs/08-Modules/stripe-payment.md) |

## For developers

Read the [documentation hub](docs/README.md) for the complete technical set. Start with the [architecture section](docs/01-Architecture/README.md), then use the [active-module catalogue](docs/08-Modules/README.md) for implementation details.

Before changing a module, inspect its manifest, bootstrap, routes, permissions, schema, integrations, and tests. Update the corresponding documentation when the public behavior changes. The [module documentation template](docs/08-Modules/_template.md) defines the expected structure.

## Repository structure

| Directory | Responsibility |
|---|---|
| `src/` and `includes/` | Core services and shared compatibility APIs. |
| `plugins/` | Active optional modules. |
| `archive/plugins/` | Historical or inactive modules. |
| `admin/` and `customer/` | Core administration and customer portal. |
| `api/` | Core API routing and authentication. |
| `db/` | Core schema and migrations. |
| `docs/` | Product, architecture, API, module, security, operations, and testing documentation. |
| `tests/` | Automated tests and conformance checks. |
| `bin/` | Operational and development utilities. |

## Product boundaries

Kohevo is not a general-purpose framework competing with Laravel or Symfony. It is a focused platform foundation for business applications. It is not an SPA-first system, a microservice mesh, or a single vertical product. Its value is the combination of a practical deployment model, shared business foundations, and modular product capabilities.

## Source of truth

For active behavior, use executable code, runtime tests, manifests, and schema/migrations first. Architecture and roadmap documents describe design direction and may differ from the current implementation. The active module pages record those differences explicitly.

## License and contribution

Review the repository’s license and contribution policy before redistribution or deployment. Contributions should keep tenant boundaries, permission checks, CSRF, audit behavior, module ownership, and documentation aligned with the implementation.
