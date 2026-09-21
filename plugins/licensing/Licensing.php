<?php
/**
 * Licensing plugin — bootstrap.
 *
 * The central license server for standalone product installs (see
 * LICENSE-SYSTEM-MASTER-SPEC/ and the narrower build plan agreed in chat).
 * Runs on its own dedicated, single-tenant Kohevo install — never alongside
 * the product plugins (Booking/Forms/etc.) it issues licenses for.
 *
 * The check-in endpoint at /licensing/check, Ed25519 signing, and the data
 * model are joined here by a minimal admin UI for managing products,
 * clients, plans, and issuing/rotating license keys.
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
        Hook::addFilter('admin_nav_items', [$this, 'addAdminNav']);
    }

    public function addPublicRoutes(array $routes): array {
        $routes['licensing/check'] = [
            'handler' => $this->dir('public/check.php'),
            'methods' => ['POST'],
        ];
        return $routes;
    }

    public function addAdminNav(array $items): array {
        if (!Auth::can('licensing.manage') && !Auth::isSuperAdmin()) return $items;

        $items[] = ['slug' => 'licensing', 'label' => __('licensing_nav_dashboard', 'Dashboard'),
                    'href' => $this->url('admin/index.php'),
                    'icon' => 'key', 'perm' => 'licensing.manage', 'order' => 500, 'group' => 'licensing'];
        $items[] = ['slug' => 'licensing-installs', 'label' => __('licensing_nav_installs', 'Licenses'),
                    'href' => $this->url('admin/installs.php'),
                    'icon' => 'shield', 'perm' => 'licensing.manage', 'order' => 501, 'group' => 'licensing'];
        $items[] = ['slug' => 'licensing-clients', 'label' => __('licensing_nav_clients', 'Clients'),
                    'href' => $this->url('admin/clients.php'),
                    'icon' => 'users', 'perm' => 'licensing.manage', 'order' => 502, 'group' => 'licensing'];
        $items[] = ['slug' => 'licensing-products', 'label' => __('licensing_nav_products', 'Products'),
                    'href' => $this->url('admin/products.php'),
                    'icon' => 'box', 'perm' => 'licensing.manage', 'order' => 503, 'group' => 'licensing'];
        $items[] = ['slug' => 'licensing-plans', 'label' => __('licensing_nav_plans', 'Plans'),
                    'href' => $this->url('admin/plans.php'),
                    'icon' => 'tag', 'perm' => 'licensing.manage', 'order' => 504, 'group' => 'licensing'];
        return $items;
    }
}
