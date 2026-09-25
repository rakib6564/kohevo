# Multilang Translate

**Status:** As built  
**Verified version:** `1.3.0`  
**Owner:** `plugins/multilang-translate`

## Purpose

Multilang Translate provides a tenant-scoped translation catalogue. Administrators define languages, scan selected URLs, harvest source strings, edit draft translations, publish by locale, import/export CSV, and configure a customer/admin/public language switcher.

## Workflow

Harvesting is **on demand** through the admin Scan workflow; normal visitor requests do not passively harvest strings. The crawler discovers selected public, customer, and admin URLs, fetches HTML, tokenizes translatable text and selected attributes, applies exclusion rules, and records occurrence metadata.

Translations use a flat normalized source-text key. A published non-default locale is replaced during output buffering. Identical source text therefore shares one translation regardless of semantic context.

The module supports draft, published, and ignored states, locale-scoped find/replace, CSV export/import, optional automatic translation through a LibreTranslate-compatible endpoint, and a floating or header-bar switcher.

## Data and permissions

The module owns `multilangtranslate_languages`, `multilangtranslate_strings`, `multilangtranslate_translations`, and `multilangtranslate_exclude_rules`. Settings use the `multilang-translate.*` namespace. Admin permissions are `mlt.view`, `mlt.manage`, and `mlt.import_export`. MCP scopes are `mlt.view` and `mlt.manage`.

The admin API is `plugins/multilang-translate/admin/api.php`. Actions include discovery, scan, save, publish, language management, find/replace, ignore, import/export, and switcher settings. All mutations use authentication, permissions, POST, and CSRF verification.

## Limits and security

Manual URLs are limited to 20,000 bytes, URL length to 2,048 characters, scan batches to two URLs, and source text nodes to 2,000 characters. Regex exclusion rules are validated before storage. The crawler marks scan requests to prevent recursive harvesting.

The crawler can fetch arbitrary HTTP(S) URLs without forwarding the operator session. Review network-egress and SSRF policy before allowing external domains. Translation replacement escapes text and attribute contexts but is not a rich-HTML sanitization system.

## Source map

See `MultilangTranslate.php`, `MultilangTranslateMcpHandler.php`, `admin/index.php`, `admin/api.php`, `includes/`, `assets/`, `install.sql`, `uninstall.sql`, and `TODO.md`.

## Known gaps

The plugin description and old TODO imply passive harvesting and future auto-translation/exclusions, but those backend features are already implemented or partially implemented. The README should be added and the remaining UI exposure for exclusion-rule management should be clarified.
