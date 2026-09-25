# MCP Gateway

**Status:** As built  
**Verified version:** `0.1.0`  
**Owner:** `plugins/mcp-gateway`

## Purpose

MCP Gateway exposes tenant-scoped tools through a JSON-RPC endpoint and provides separate admin and customer AI assistant experiences. It centralizes token authentication, scope checks, redaction, audit logging, rate limiting, and tool dispatch for installed-module handlers.

## Endpoints and administration

The external endpoint is `POST /api/v1/mcp`, available only when the plugin is active and `MCP_GATEWAY_ENABLED` is enabled. It implements MCP initialize, notifications/initialized, tools/list, and tools/call behavior. The customer assistant is `POST /ai-assistant` and is independently controlled by the `mcp-gateway.customer_assistant_enabled` setting.

Administrators with `mcp-gateway.manage` manage tokens, AI connection settings, and the session-backed AI Command Center. Token creation displays a raw bearer token once, stores only a SHA-256 hash, supports selected scopes, and permits expiry and revocation.

## Tool model

Core tools cover settings, media, notifications, audit search, bounded log tail, migration status, information-schema inspection, cron triggers, bounded test execution, and business reports. Installed handlers add Booking, Membership, Coaching, Forms, Stripe read-only, and Multilang tools. Write tools are not automatically available: the token must carry the exact scope and the handler must accept the operation.

The admin assistant runs read-only-looking tools automatically and pauses writes for confirmation/editing. The customer assistant is restricted to public services, plans, and the signed-in customer’s own membership/dashboard context.

## Data and security

The module owns `mcp_tokens` and `mcp_action_log`. Tokens are tenant-scoped and revocable. External calls default to 60 calls per token per minute. Admin-chat calls use token ID zero and bypass that external token limiter. Tool names and arguments are redacted for password-, secret-, and token-shaped keys, and attempts are audited.

The gateway has hard blocks for password/reset, role or user deletion, Stripe secrets, SMTP passwords, and gateway disable operations. The denylist is pattern-based, not a formal capability system, so module scopes remain the primary security boundary.

## AI provider

The admin assistant uses an OpenAI-compatible `/chat/completions` endpoint configured through encrypted settings. The customer assistant uses the same provider path but has a separate 30-message-per-hour session cap and a 2,000-character input limit. Provider failures return bounded error responses.

## Source map

See `McpGateway.php`, `McpGatewayAPI.php`, `CoreMcpTools.php`, `ReportingMcpTools.php`, `AiProviderClient.php`, `CustomerAssistant.php`, `admin/`, `install.sql`, and the module-specific `*McpHandler.php` files.

## Known gaps

The module README does not fully document the finite installed-tool catalogue, MCP protocol error model, customer-assistant separation, admin-chat limiter bypass, or exact scope matrix. The provider base URL is configurable, so outbound network trust must be reviewed operationally.
