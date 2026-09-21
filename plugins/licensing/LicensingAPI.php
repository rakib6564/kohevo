<?php
/**
 * Licensing plugin — data access.
 *
 * Phase 1 scope: schema only. ensureSchema() keeps an already-active install
 * current after an upgrade ships new CREATE TABLE IF NOT EXISTS statements —
 * same self-heal idiom Membership/Booking/Stripe already use, replaying
 * install.sql itself rather than duplicating DDL in PHP.
 */

declare(strict_types=1);

class LicensingAPI {

    private static bool $schemaChecked = false;

    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        $file = __DIR__ . '/install.sql';
        if (!is_file($file)) return;
        $sql = (string) file_get_contents($file);
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '') continue;
            try {
                Database::query($stmt);
            } catch (\Throwable $e) {
                slate_log('Licensing ensureSchema statement failed: ' . $e->getMessage(), 'error');
            }
        }
    }
}
