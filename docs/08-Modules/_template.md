# Module Name

**Status:** As built / Architecture / Roadmap  
**Verified version:** `x.y.z`  
**Owner:** `plugins/<slug>`

## Purpose

State the user and system problem this module solves. Keep this section limited to implemented behavior when the status is **As built**.

## Surface

Document public, customer, admin, API, MCP, Content Builder, and programmatic entry points. Include exact paths and route parameters.

## Capabilities

Describe the important workflows in complete sentences. Separate active behavior from planned behavior.

## Data and ownership

List owned tables, settings, files, tenant keys, indexes, runtime schema reconciliation, and uninstall behavior. State which core or module data is delegated rather than owned.

## Permissions and scopes

List admin permissions, MCP scopes, authentication requirements, and CSRF requirements. Explain super-admin or public-route exceptions.

## Integrations and hooks

List implemented integrations and hooks. Mark manifest-only recommendations and planned contracts as **declared-only** or **planned**.

## Operations and lifecycle

Document cron, queues, webhooks, retries, idempotency, external calls, cleanup, and failure states.

## Security and limits

Document secrets, token or link behavior, uploads, SSRF, rate limits, PII, tenant isolation, size/time limits, and known security gaps.

## Source map

List the implementation files reviewed, including the manifest, bootstrap, routes, APIs, schema, migrations, uninstall script, and important integration handlers.

## Known gaps

Record discrepancies between code, README, manifest, architecture documents, and roadmap. Do not silently convert intended behavior into an active contract.
