# Archived plugins

These plugins ship with Slate but are **not part of the Solaya build**.
They were moved out of `plugins/` so `PluginLoader` never loads them —
it resolves plugins only at `SLATE_ROOT/plugins/<slug>`, so anything in
here is inert.

Scope comes from *Solaya — Capacités & Parcours / Capabilities &
Journeys*, which names the build as Booking, Booking+, Membership and
Coaching. See the plugin table in the root `README.md` for what stayed
and why.

| Plugin | Why it's archived |
|---|---|
| `clientdesk` | Freelancer project portal — not in the spec |
| `content-builder` | CMS/page builder; Booking and Membership only use it opportunistically (`class_exists`-guarded) |
| `flat-rate-shipping` | E-commerce shipping |
| `react-site-bridge` | React front-end content bridge |
| `restaurant` | Restaurant suite |
| `seo` | Per-page meta; only useful alongside `content-builder` |
| `shipping-flat-rate` | E-commerce shipping |
| `shop` | E-commerce |
| `shop-emails` | Requires `shop` |
| `sitehub` | WordPress site control plane |
| `slate-mcp` | AI/MCP access tools |
| `small-business-kit` | Generic marketing templates |
| `studio` | Dance/arts studio management |
| `timeclock` | Construction time tracking |

## Restoring one

```bash
git mv archive/plugins/<slug> plugins/<slug>
```

Then activate it in **Admin → Plugins**.

## Note on the database

Moving a directory does not change the `plugins` table. Any archived
slug still marked `status='active'` there makes `PluginLoader` log
"Plugin '<slug>' is marked active but directory missing." — harmless,
but noisy. To silence it:

```sql
UPDATE plugins SET status = 'inactive'
WHERE slug IN ('clientdesk','content-builder','flat-rate-shipping',
  'react-site-bridge','restaurant','seo','shipping-flat-rate','shop',
  'shop-emails','sitehub','slate-mcp','small-business-kit','studio',
  'timeclock');
```
