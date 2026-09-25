# Booking

**Status:** As built
**Verified version:** `0.9.0`
**Owner:** `plugins/booking`

## Purpose

Booking manages services, providers, schedules, availability, appointments, customers, payments, reminders, follow-ups, resources, locations, coupons, gift cards, invoices, messages, and calendar synchronization.

## User and admin surfaces

The public booking experience is `/book`. It supports service/provider/date/slot selection, customer details, add-ons, custom fields, coupons, gift cards, party size, recurrence up to 12 occurrences, multi-slot booking, embedded or hosted payment, confirmation, and manage-link cancellation or rescheduling.

Admin routes cover dashboard, calendar, appointments, services, categories, add-ons, fields, providers, hours, breaks, overrides, locations, resources, restrictions, service rules, customers, coupons, gift cards, invoices, messages, settings, and Google Calendar connection. The module also registers `/api/v1/booking/*`, MCP tools, a Content Builder block, customer dashboard widgets, and portal navigation.

## Data ownership

The module owns tenant-scoped service, category, add-on, custom-field, provider, schedule, location, resource, appointment, customer, coupon, gift-card, Google busy-block, and Booking+ configuration tables. `booking_customers` is an email-keyed booking profile used by the active code; it is not merely a pointer to the core Contact model.

Appointments track status, payment state, invoice and Stripe identifiers, recurrence, manage tokens, customer metadata, and calendar synchronization state. Migrations from `0.2.0` through `0.9.0` add columns and indexes, while runtime schema checks reconcile missing columns.

## Payments and calendar

Stripe hosted Checkout and PaymentIntent flows support full payment, deposits, taxes, discounts, gift cards, payment retries, and refunds. Payment completion is verified server-side. Google Calendar supports provider OAuth, event push for created/cancelled/rescheduled appointments, external busy-block pulls, webhook validation, watch renewal, and retry/safety cron.

Optional Booking+ capabilities add prerequisites, slot restrictions, service rules, messaging, metadata, and custom reminders. Twilio SMS/WhatsApp, loyalty points, and notification behavior are also implemented as optional settings or integrations.

## Lifecycle and security

Availability is recalculated server-side and appointment creation protects against conflicting slots. Public manage links use high-entropy bearer tokens. Admin mutations require permissions and CSRF. Google OAuth uses a session state nonce, and the calendar webhook validates channel/resource data.

Cron handles reminders, follow-ups, messaging nudges, Google pulls, watch renewal, and failed sync retries. External HTTP calls are synchronous and bounded. Public rate limiting was not found in the module, so deployments should apply host-level controls.

## Permissions

The main permissions are `booking.view`, `booking.manage_services`, `booking.manage_providers`, `booking.manage_appointments`, `booking.manage_resources`, `booking.manage_payments`, and `booking.manage_settings`.

## Hooks and integrations

Active hooks include booking-created, status-changed, cancelled, rescheduled, paid, and payment-failed actions, availability filters, reminder-body filters, customer portal widgets, and Content Builder registration. Integrations include Stripe Payment, Google Calendar, optional Booking+, Twilio, Mailer, Notifications, AuditLog, MCP, and the public/API routers.

## Source map and known gaps

See `Booking.php`, `BookingAPI.php`, `BookingApiHandler.php`, `BookingMcpHandler.php`, `BookingPlusAPI.php`, `GoogleCalendarSync.php`, `public/`, `admin/`, migrations, `install.sql`, and `uninstall.sql`.

The older module design page described Google sync as planned, a Contact-owned customer model, generic PaymentGateway/Money contracts, and queued notifications. Those are not the active implementation contract. `uninstall.sql` also does not remove every migrated table or setting.
