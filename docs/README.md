# Kohevo Documentation

This documentation set describes the Kohevo platform from the system level down to individual active modules and operational procedures.

## Reading order

Start with [Vision](00-Vision/README.md), then read the [Architecture](01-Architecture/README.md), [Standards](03-Standards/README.md), [Security](10-Security/README.md), and [Operations](13-Operations/README.md) sections. Use the [Active Module Catalogue](08-Modules/README.md) when working on a plugin.

## Sections

| Section | Purpose |
|---|---|
| [00 — Vision](00-Vision/README.md) | Product direction and platform goals. |
| [01 — Architecture](01-Architecture/README.md) | Runtime, tenancy, modules, services, events, and dependencies. |
| [02 — Domain](02-Domain/README.md) | Shared domain concepts such as identity and contacts. |
| [03 — Standards](03-Standards/README.md) | Development, compatibility, versioning, and platform standards. |
| [04 — Design System](04-Design-System/README.md) | Tokens, components, accessibility, and visual conventions. |
| [05 — Rendering](05-Rendering/README.md) | Pages, blocks, themes, assets, and SEO rendering. |
| [06 — SDK](06-SDK/README.md) | Plugin contracts and module development. |
| [07 — API](07-API/README.md) | Public APIs, payments, webhooks, errors, and authentication. |
| [08 — Modules](08-Modules/README.md) | Verified reference pages for active plugins. |
| [09 — Roadmap](09-Roadmap/README.md) | Planned work and design proposals. |
| [10 — Security](10-Security/README.md) | Authentication, authorization, error handling, and security controls. |
| [11 — Database](11-Database/README.md) | Schema conventions, migrations, and persistence patterns. |
| [12 — Testing](12-Testing/README.md) | Test strategy and conformance checks. |
| [13 — Operations](13-Operations/README.md) | Deployment, backups, logs, performance, and shared hosting. |
| [14 — ADR](14-ADR/README.md) | Architectural decision records. |

## Documentation status labels

- **As built:** verified against active implementation for a stated version.
- **Architecture:** platform-level rule or target contract.
- **Roadmap:** proposed behavior that is not an active contract.
- **Historical:** retained for context and not used as an implementation reference.

If a design document describes a future contract that differs from active code, the active module page must state the difference explicitly.
