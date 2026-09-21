<?php
/**
 * Licensing plugin — bootstrap.
 *
 * The central license server for standalone product installs (see
 * LICENSE-SYSTEM-MASTER-SPEC/ and the narrower build plan agreed in chat).
 * Runs on its own dedicated, single-tenant Kohevo install — never alongside
 * the product plugins (Booking/Forms/etc.) it issues licenses for.
 *
 * Phase 3 (this version): the check-in endpoint at /licensing/check joins
 * Phase 1's data model and Phase 2's Ed25519 signing. Still no admin UI —
 * products/clients/plans/installs are managed by hand (Database) until a
 * later phase.
 */

require_once __DIR__ . '/LicensingAPI.php';

class Licensing extends Plugin {

    public function boot(): void {
        // Schema self-heal, stamped once per version — mirrors Membership.
        if ((string) $this->setting('schema_verified', '') !== $this->version) {
            LicensingAPI::ensureSchema();
            $this->setSetting('schema_verified', $this->version);
        }

        Hook::addFilter('public_routes', [$this, 'addPublicRoutes']);
    }

    public function addPublicRoutes(array $routes): array {
        $routes['licensing/check'] = [
            'handler' => $this->dir('public/check.php'),
            'methods' => ['POST'],
        ];
        return $routes;
    }
}
