# Membership

**Status:** As built
**Verified version:** `0.7.1`
**Owner:** `plugins/membership`

## Purpose

Membership manages fixed-term membership, insurance, and course plans; online and manual subscriptions; customer onboarding and profiles; QR member cards; wallet balances; Booking course access; and customer/admin membership surfaces.

## Plans and subscriptions

Plans contain bilingual names/descriptions, type, price, currency, duration, grace period, session quota, insurance mode, optional Booking course ID, active state, and sort order. Subscriptions track status, date range, grace, payment source, Stripe identifiers, insurance fee, reminders, and cancellation.

Purchase creates a pending Stripe Checkout session. A verified webhook or return flow activates the subscription, records the charge, and creates the wallet purchase ledger entry. Administrators can also assign or activate subscriptions manually. Customers can self-cancel through the portal.

## Customer experience

The public marketing route is the Membership landing page. The customer route provides plan purchase, onboarding, profile, dashboard, schedule, wallet, and QR card views. Onboarding collects gender, date of birth, skill, medical/allergy notes, emergency contact, phone, avatar, terms/media consent, and locale.

The Booking integration can require an active course subscription, completed profile, quota, and configured insurance for eligible online/self-service course bookings. It does not globally gate every Booking service.

## Data and permissions

The module owns `membership_plans`, `membership_subscriptions`, `membership_profiles`, `membership_wallet`, and `membership_wallet_txns`. Core customer identity is reused. Money is stored in integer cents in this module. Runtime `ensureSchema()` is used alongside `install.sql`; uninstall drops membership tables but settings cleanup is platform-dependent.

Admin permissions are `membership.view`, `membership.manage_plans`, `membership.manage_members`, and `membership.manage_settings`. MCP handlers use `membership.read`, `membership.manage_members`, and `membership.manage_plans` scopes.

## Integrations and limits

Stripe Payment verifies sessions and processes webhooks. Booking uses a `booking_can_book` filter and course attendance/quota queries. Customer registration provisions profile and wallet. Media uploads support avatars with a 4 MB limit and constrained image types. English and French language paths are bundled.

The QR card uses a member token and a vendored QR generator. Subscriptions and wallet transactions are tenant-scoped. Admin mutations use permission checks and CSRF. Customer portal actions verify customer ownership.

## Known gaps

The former module design described generic PaymentGateway/Money contracts, membership events, Contacts-only identity, and generic billing/benefits routes that are not the current implementation. The active code directly uses StripePaymentAPI, Booking hooks, concrete membership tables, and MCP handlers. Reminder columns and settings exist, but no active expiry reminder cron sender was found. There is no discovered universal membership event publication.

## Source map

See `Membership.php`, `MembershipAPI.php`, `MembershipMcpHandler.php`, `install.sql`, `uninstall.sql`, `public/`, `admin/`, and the QR and language assets.
