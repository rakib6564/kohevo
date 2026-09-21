<?php
/**
 * Licensing plugin — bootstrap.
 *
 * The central license server for standalone product installs (see
 * LICENSE-SYSTEM-MASTER-SPEC/ and the narrower build plan agreed in chat).
 * Runs on its own dedicated, single-tenant Kohevo install — never alongside
 * the product plugins (Booking/Forms/etc.) it issues licenses for.
 *
 * Phase 1 (this version): data model only. No admin UI, no public check-in
 * endpoint yet — those are later phases on top of LicensingAPI.
 */

require_once __DIR__ . '/LicensingAPI.php';

class Licensing extends Plugin {

    public function boot(): void {
        // Schema self-heal, stamped once per version — mirrors Membership.
        if ((string) $this->setting('schema_verified', '') !== $this->version) {
            LicensingAPI::ensureSchema();
            $this->setSetting('schema_verified', $this->version);
        }
    }
}
