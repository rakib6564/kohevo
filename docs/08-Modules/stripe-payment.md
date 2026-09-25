# Stripe Payment

**Status:** As built  
**Verified version:** `1.2.1`  
**Owner:** `plugins/stripe-payment`

## Purpose

Stripe Payment provides hosted Checkout and embedded Payment Element flows, webhook verification, a charge ledger, refunds, administrative settings, and server-side Stripe Terminal wrappers. The checkout completion paths are currently coupled to Shop.

## Checkout and webhook surfaces

Hosted and embedded checkout are registered through the Shop integration. Embedded Payment Element sessions use `public/create-intent.php`; browser return processing uses `public/return.php`; hosted Checkout returns through `public/success.php`. Stripe sends signed events to `public/webhook.php`.

The webhook supports `checkout.session.completed` and `payment_intent.succeeded`, verifies the Stripe signature and replay window, records charges, dispatches `stripe_webhook_event`, and attempts idempotent Shop order completion. The module also exposes admin settings and a charge/refund page. No Terminal route is present, although `StripeTerminalAPI` wraps Stripe Terminal resources for server-side callers.

## Data and configuration

The module owns `stripepayment_sessions` for Shop checkout mappings and `stripepayment_charges` for the tenant-scoped charge ledger. Settings under `stripe-payment.*` include secret and publishable keys, mode, webhook secret, flow visibility, countries, payment methods, wallets, and Terminal configuration.

Secret keys are encrypted at rest. Amounts are converted to Stripe minor units, including a hard-coded zero-decimal currency list. The implementation directly uses `StripePaymentAPI` and `ShopAPI`, rather than the provider-neutral `PaymentGateway` and `Money` contracts described by some architecture documents.

## Permissions and operations

`stripe.manage_settings` protects gateway configuration and connectivity tests. `stripe.manage_charges` protects charge viewing and full or partial refunds. The MCP integration exposes only `stripe.read` tools for charge listing, detail, and webhook health.

Webhook and browser paths re-fetch provider objects and verify status, amount, and currency on the server. Provider IDs have uniqueness constraints and successful charge recording is designed to be idempotent.

## Security and failure behavior

Webhook signatures are mandatory and stale signatures are rejected. Card data stays in Stripe Checkout or Stripe.js. Admin mutations use CSRF protection. Public endpoints rely on Stripe verification, Shop cart/session state, and server-side provider retrieval rather than user permissions.

Known operational risks include a possible stale claimed webhook row after fatal process termination, a race between webhook and browser completion paths, no queue or retry worker, and no module-level rate limiting. Unknown mappings may be acknowledged with HTTP 200 and can therefore require manual reconciliation.

## Source map

See `StripePayment.php`, `StripeAPI.php`, `StripePaymentAPI.php`, `StripeTerminalAPI.php`, `StripePaymentMcpHandler.php`, `public/`, `admin/`, `install.sql`, and `uninstall.sql`.

## Known gaps

The README and generic payment documents omit embedded checkout, the required second webhook event, Terminal wrappers, charges/refunds, Shop coupling, and claim-recovery risks. Update those documents before treating the provider-neutral payment architecture as an active implementation contract.
