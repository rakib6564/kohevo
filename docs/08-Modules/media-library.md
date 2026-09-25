# Media Library

**Status:** As built compatibility shim  
**Verified version:** `1.1.0`  
**Owner:** `plugins/media-library`

## Purpose

The active Media Library plugin preserves legacy `MediaLibrary` APIs and picker assets while delegating the actual library to the core Media service. The active user interface is `/admin/media.php`; the historical plugin URL redirects there.

## Storage and ownership

The core service owns `media_files` metadata and `media_usage` references. New files are stored below `/uploads/media/<year>/<month>/`. The shim recognizes legacy paths under media, shop products, and branding and contributes usage data through the `media_usage` filter.

The legacy `medialibrary_files` table exists for compatibility and migration. The shim’s uninstall SQL drops only that legacy cache table; it does not remove core media metadata or files.

## Surface

`/plugins/media-library/admin/index.php` redirects authenticated users to `/admin/media.php`. The legacy JSON endpoint `/plugins/media-library/admin/api.php?action=list` exposes tenant-scoped read-only listing for images, documents, in-use, and unused files. Picker assets expose `MediaPicker` and `SlateMedia` globals and support single replacement, gallery append, search, and cached results.

Core administration supports browse, search, pagination, upload, import, and safe deletion. The active library supports images and documents, not only images.

## Permissions and security

Core permissions are `media.view`, `media.upload`, and `media.delete`; they are not declared in the plugin manifest. Uploads and imports require CSRF-protected admin actions. Deletion requires an approved managed path and zero usage count.

Path validation rejects URLs, traversal, backslashes, and paths outside approved upload roots. SVG sanitization removes active-content elements and dangerous attributes. Upload size and MIME limits are enforced by the core Uploads service.

## Integrations

The shim integrates with core Media, Uploads, Database, Auth, AuditLog, Hook, tenant context, and i18n. It has no public customer route, payment flow, webhook, or module-owned content workflow.

## Source map

See `MediaLibrary.php`, `admin/index.php`, `admin/api.php`, `assets/js/picker.js`, `assets/css/picker.css`, `install.sql`, `uninstall.sql`, `src/Services/Media/Media.php`, `admin/media.php`, and `db/schema.sql`.

## Known gaps

The module README describes the former plugin-owned image library and is stale. It should not be used as the active storage or permission contract. The picker requests a very large list for client-side filtering, which may be expensive for large libraries. Legacy usage matching is path-based and may not detect manually stored alternate URL formats.
