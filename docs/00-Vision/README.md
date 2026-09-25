# Kohevo Vision

**Status:** Product direction
**Product:** Kohevo

## What Kohevo is

Kohevo is a modular business platform for organizations that need to manage customer relationships, scheduled services, memberships, forms, payments, communication, content, and recurring operations in one coherent product.

Kohevo is deliberately broader than a single vertical application. A booking business, coaching practice, membership organization, education provider, studio, agency, or specialized SaaS product can assemble the modules it needs on the same core foundation.

## The product promise

Kohevo aims to make it practical to build and operate a professional customer-facing business system without combining many disconnected platforms or starting every project from zero.

The promise has four parts:

1. **Compose a product.** Use shared identity, tenancy, permissions, rendering, data, media, payments, forms, and automation foundations, then add business modules.
2. **Fit the workflow.** Configure plans, services, forms, customer journeys, content, languages, and permissions around the organization rather than forcing one business model.
3. **Deploy practically.** Run the same product on ordinary PHP hosting, a VPS, or larger infrastructure without requiring a server-side build pipeline.
4. **Grow without a platform rewrite.** Preserve module boundaries and stable contracts so new capabilities can be introduced without duplicating the core.

## Product principles

### Business context before feature count

Kohevo should help an organization complete real customer and operational journeys. A feature belongs when it improves a journey such as discovering a service, submitting information, booking, paying, joining, participating, communicating, or returning.

### One shared foundation

Identity, tenant isolation, roles, permissions, media, settings, payments, mail, audit logging, and routing should have one clear owner. Modules should extend these capabilities rather than create competing copies.

### Modular, not fragmented

The product is a modular monolith. Modules are separated by explicit responsibilities and integration points while remaining simple to deploy and operate as one application.

### Trustworthy operations

External calls, webhooks, scheduled work, sensitive data, uploads, backups, payment state, and customer access require bounded behavior, auditability, and documented failure modes.

### Progressive enhancement

Core pages should remain usable as server-rendered HTML. JavaScript can improve live chat, forms, pickers, charts, and payment experiences without making the complete product dependent on a client-side application build.

### Clear difference between current and future

As-built behavior, architecture, and roadmap proposals must be labeled separately. Product decisions should not be based on a feature that exists only in a design document.

## Target markets and use cases

Kohevo is especially relevant to appointment-led and relationship-led organizations:

- health, wellness, nutrition, therapy, and coaching;
- fitness, yoga, studios, and classes;
- professional services and consultations;
- education, training, and structured programs;
- memberships, associations, and communities;
- agencies and white-label client platforms;
- local service businesses; and
- specialized vertical SaaS products.

These markets share common needs: customer identity, structured intake, scheduling or enrollment, payment, access rules, ongoing communication, and operational reporting. Kohevo provides those foundations as composable modules.

## What Kohevo is not

Kohevo is not a general-purpose framework, an SPA-only product, a microservice mesh, or a single-purpose booking application. It is also not a promise that every listed module is appropriate for every organization. The product should remain focused on coherent business operations and clear internal boundaries.

## Long-term direction

Over time, Kohevo should become a reliable foundation for specialized business products. The platform should make it easier to launch a new vertical, white-label an existing workflow, support multiple tenants, add new integrations, and operate the system safely without sacrificing understandability.

The architecture, standards, SDK, module catalogue, operations guides, security documentation, and ADRs should all support that direction while remaining honest about current implementation behavior.
