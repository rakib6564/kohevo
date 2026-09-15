<?php
/**
 * Slate — TenantBranding: reads the tenant's Global Site Settings (accent/text/
 * canvas colors, heading/body fonts) into the plain array shape
 * TenantThemeResolver::fromBrand() expects.
 *
 * Backs admin/editor.php's "Site Settings" panel (the editor's own in-context
 * equivalent of admin/settings.php's Branding section — same brand_accent_color
 * setting key, so the two never drift). Split out as its own tiny class
 * (rather than inlined in ContentServiceFactory) because it is the one place
 * in the Phase 2 stack allowed to read the database for this purpose —
 * TenantThemeResolver itself must stay pure (Presentation may not read the
 * database; see its own docblock).
 */

declare(strict_types=1);

namespace Slate\Services\Content;

final class TenantBranding
{
    /** Setting keys this panel owns — the Site Settings AJAX handler in
     * admin/editor.php writes exactly these, nothing else. */
    public const KEYS = [
        'accent' => 'brand_accent_color',
        'ink' => 'brand_text_color',
        'pageBg' => 'brand_canvas_color',
        'heading' => 'brand_heading_font',
        'body' => 'brand_body_font',
    ];

    /** @return array<string,string> the TenantThemeResolver::fromBrand() input shape */
    public static function resolve(): array
    {
        $out = [];
        foreach (self::KEYS as $brandKey => $settingKey) {
            $out[$brandKey] = (string) (\Database::setting($settingKey) ?? '');
        }
        return $out;
    }
}
