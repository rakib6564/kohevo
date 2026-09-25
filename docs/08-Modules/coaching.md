# Coaching

**Status:** As built  
**Verified version:** `0.6.0`  
**Owner:** `plugins/coaching`

## Purpose

Coaching provides a membership-gated three-month Body & Soul program with client profiles, goals, daily check-ins, food and mood diary, hydration, activity, charts, chat, motivation, recipes, shopping lists, meal structures, and end-of-program summaries.

## Enrollment and surfaces

A customer is enrolled only when Membership is active, `coaching.program_plan_id` is configured, and the customer’s active subscription matches that plan ID exactly. Coaching adds one Program entry to the customer portal and serves `/coaching` views for home, profile, goals, diary, entries, charts, chat, meal structure, shopping, recipes, motivation, and summary.

Admin pages cover overview, client roster, client detail, feed, chat, library, motivation, and settings. `frequent_cron` delivers scheduled messages and generates summaries for memberships approaching expiry.

## Data ownership

The module owns 16 tenant-scoped tables: `coaching_profile`, `coaching_goal`, `coaching_goal_checkin`, `coaching_extra_action`, diary entry/food/photo tables, hydration, activity, thread/message, meal structure, shopping list, recipe, challenge, and summary tables. The module uses `customer_id` rather than duplicating customer identity.

Profile saves recompute BMI, BMR, and TDEE. Goals support daily, weekly, monthly, general, and personal scopes. Check-ins support not achieved, partial, achieved, and exceeded, with one check-in per goal/day. Optional meal structure, shopping, and recipe areas are enabled per client.

## Chat and content

Each customer has one chat thread. Customer messages can include photos; practitioner messages can be immediate or scheduled. The customer router polls for new messages, while cron delivers messages with a future `send_at` value. Practitioner library templates use `customer_id = NULL`; assigning a template creates an independent customer copy.

The MCP integration supports reading clients and threads, sending practitioner chat, and upserting library meal structures, shopping lists, and recipes without delete operations.

## Permissions and security

Admin permissions are `coaching.view_clients`, `coaching.manage_clients`, `coaching.reply_chat`, and `coaching.manage_library`. Customer routes require authentication, CSRF for POST actions, and the Membership enrollment gate. MCP scopes are separate and explicitly checked. Diary photos are capped at 10 MB, and ownership predicates include tenant and customer IDs in the principal read/write paths.

## Integrations and known gaps

The hard runtime integration is Membership. The manifest recommends Booking, Media Library, and Forms, but concrete runtime handlers for those integrations were not found in the active module audit. The module uses core hooks, portal widgets, notifications, settings, and cron.

The manifest and old implementation notes describe early waves, although later features are active. The customer activity hook contains a likely `coaching_goals` versus `coaching_goal` table-name defect that can silently suppress activity data. Runtime `ensureSchema()` is also legacy behavior that differs from the platform’s migration-first architecture target.

## Source map

See `Coaching.php`, `CoachingAPI.php`, `CoachingCharts.php`, `CoachingMcpHandler.php`, `install.sql`, `uninstall.sql`, `customer/router.php`, and `admin/`.
