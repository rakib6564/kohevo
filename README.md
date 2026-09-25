# Kohevo

Kohevo is a modular, tenant-aware PHP platform for operating websites, customer portals, bookings, memberships, forms, coaching programs, payments, media, translation, backups, licensing, and AI-assisted administration.

The platform is designed as a **modular monolith**. Core services provide identity, tenancy, rendering, database access, authentication, authorization, hooks, settings, media, audit logging, and scheduled execution. Optional plugins add business capabilities without requiring a framework build step.

## Documentation policy

This repository distinguishes between three kinds of documentation:

- **As-built documentation** describes behavior verified against the active PHP implementation.
- **Architecture and standards documentation** describes platform conventions and stable development rules.
- **Roadmap and design documentation** describes proposed work and is labeled as such.

Module pages under [`docs/08-Modules`](docs/08-Modules/README.md) are the authoritative functional reference for active plugins. Each page records its verified version, routes, tables, permissions, integrations, operational behavior, security limits, and known gaps.

## Start here

| Need | Read |
|---|---|
| Install and configure the platform | [`INSTALL.md`](INSTALL.md) and [`docs/13-Operations/deployment.md`](docs/13-Operations/deployment.md) |
| Understand the runtime | [`docs/01-Architecture`](docs/01-Architecture/README.md) |
| Build a plugin | [`docs/06-SDK/building-a-module.md`](docs/06-SDK/building-a-module.md) |
| Review active modules | [`docs/08-Modules/README.md`](docs/08-Modules/README.md) |
| Review cross-module dependencies | [`docs/01-Architecture/dependency-map.md`](docs/01-Architecture/dependency-map.md) |
| Review security controls | [`docs/10-Security`](docs/10-Security/README.md) |
| Deploy and operate the system | [`docs/13-Operations`](docs/13-Operations/README.md) |
| Review the database | [`docs/11-Database`](docs/11-Database/README.md) |
| Run tests safely | [`docs/13-Operations/running-tests-safely.md`](docs/13-Operations/running-tests-safely.md) |

## Active modules

- [Backups](docs/08-Modules/backups.md)
- [Booking](docs/08-Modules/booking.md)
- [Coaching](docs/08-Modules/coaching.md)
- [Forms](docs/08-Modules/forms.md)
- [Licensing](docs/08-Modules/licensing.md)
- [MCP Gateway](docs/08-Modules/mcp-gateway.md)
- [Media Library](docs/08-Modules/media-library.md)
- [Membership](docs/08-Modules/membership.md)
- [Multilang Translate](docs/08-Modules/multilang-translate.md)
- [Stripe Payment](docs/08-Modules/stripe-payment.md)

The [`archive/`](archive/) directory contains historical or inactive plugins. Archived code is not part of the active product contract unless a page explicitly says otherwise.

## Runtime model

A request is resolved through the core bootstrap, tenant context, authentication state, plugin lifecycle, route handler, service/API layer, and rendering or JSON response. Plugins register behavior through hooks and filters and should use core services rather than duplicating identity, tenancy, media, payment, or rendering logic.

The system supports shared-hosting deployment through database-backed state and cron execution. Heavier deployments can substitute infrastructure drivers where documented, but module behavior should remain stable.

## Development principles

1. Keep tenant boundaries explicit in every query and write path.
2. Use core authentication, permissions, CSRF, settings, audit, media, and mail services.
3. Treat plugin manifests, routes, schemas, runtime migrations, and documentation as one versioned contract.
4. Distinguish implemented behavior from planned behavior.
5. Make external calls verifiable, bounded, logged, and retry-safe where possible.
6. Document sensitive data, retention, failure modes, and uninstall behavior.
7. Preserve backward compatibility intentionally and record breaking changes.

## Repository structure

| Directory | Responsibility |
|---|---|
| `src/` and `includes/` | Core services, shared helpers, and compatibility APIs. |
| `plugins/` | Active optional modules. |
| `archive/plugins/` | Historical or inactive modules. |
| `admin/` | Core administration routes and pages. |
| `customer/` | Core customer portal routes and pages. |
| `api/` | Core API routing and authentication. |
| `db/` | Core schema and migrations. |
| `docs/` | Architecture, standards, APIs, modules, operations, security, testing, and ADRs. |
| `tests/` | Automated tests and conformance checks. |
| `bin/` | Operational and development command-line utilities. |

## Source of truth

When documents disagree, use this order:

1. Active executable code and runtime tests.
2. Active schema/migrations and plugin manifests.
3. Module README and operational configuration.
4. Architecture and roadmap documents, which may describe intended future contracts.

Known discrepancies are recorded on the relevant module page instead of being silently hidden.

## Contribution workflow

Before changing a module, read its canonical page, inspect the module manifest and schema, update the implementation, add or update tests, and revise the module page if the public contract changes. Keep commits focused and record architectural decisions in [`docs/14-ADR`](docs/14-ADR/README.md).

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for repository contribution rules.
