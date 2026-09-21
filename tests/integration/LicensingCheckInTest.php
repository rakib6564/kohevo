<?php
/**
 * Phase 3 of the remote license server build: LicensingAPI::handleCheckIn(),
 * the business logic behind POST /licensing/check.
 *
 * Calls handleCheckIn() directly rather than driving it over real HTTP —
 * the thin shim in public/check.php (body parsing, status code, headers)
 * was verified manually end-to-end via curl against the actual route
 * (including the framework's own 405 method gate and malformed-JSON
 * handling); what's worth locking in as an automated regression is the
 * business logic itself.
 *
 * The anti-enumeration invariant — a wrong license key and a wrong product
 * slug must be indistinguishable — is asserted by comparing the two
 * response bodies for literal equality, not just "both are errors".
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/plugins/licensing/LicensingAPI.php';
require_once dirname(__DIR__, 2) . '/plugins/licensing/client/LicenseSignatureVerifier.php';

function licchk_teardown(): void {
    $file = __DIR__ . '/../../plugins/licensing/uninstall.sql';
    $sql  = preg_replace('/^--.*$/m', '', (string) file_get_contents($file));
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt === '') continue;
        Database::query($stmt);
    }
    Database::query("DELETE FROM settings WHERE setting_key IN ('licensing.signing_public_key', 'licensing.signing_secret_key')");
}

function licchk_setup(): array {
    $prop = new ReflectionProperty(LicensingAPI::class, 'schemaChecked');
    $prop->setValue(null, false);
    LicensingAPI::ensureSchema();
    LicensingAPI::storeSigningKeypair(LicensingAPI::generateSigningKeypair());

    $productId = Database::insert('licensing_products', ['slug' => 'kohevo', 'name' => 'Kohevo']);
    $clientId  = Database::insert('licensing_clients', ['name' => 'Test Client']);
    $planId    = Database::insert('licensing_plans', [
        'product_id' => $productId, 'slug' => 'pro', 'name' => 'Pro',
        'entitlements_json' => json_encode(['white_label']),
    ]);
    $installId = Database::insert('licensing_installs', [
        'client_id' => $clientId, 'product_id' => $productId, 'plan_id' => $planId,
        'label' => 'Test install', 'domain' => 'test.example',
        'license_key_hash' => hash('sha256', 'correct-key'),
        'status' => 'active',
    ]);

    return compact('productId', 'clientId', 'planId', 'installId');
}

unit('handleCheckIn(): a valid key returns a signed payload that verifies with the stored public key, with correct fields', function () {
    $ids = licchk_setup();
    try {
        $result = LicensingAPI::handleCheckIn([
            'product' => 'kohevo', 'license_key' => 'correct-key',
            'install_id' => 'uuid-1', 'domain' => 'test.example', 'app_version' => '1.0.0',
        ], '203.0.113.9');

        assert_eq(200, $result['http_status']);
        assert_true(isset($result['body']['payload'], $result['body']['signature']), 'expected payload+signature envelope');

        $verifier = new LicenseSignatureVerifier((string) LicensingAPI::signingPublicKey());
        assert_true($verifier->verify($result['body']['payload'], $result['body']['signature']), 'signature must verify with only the public key');

        $decoded = json_decode($result['body']['payload'], true);
        assert_eq('active', $decoded['status']);
        assert_eq('pro', $decoded['plan']);
        assert_eq(['white_label'], $decoded['entitlements']);
        assert_null($decoded['expires_at']);
        assert_eq(86400, $decoded['next_check_after']);
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): a successful check-in logs a checkins row and updates the install row', function () {
    $ids = licchk_setup();
    try {
        LicensingAPI::handleCheckIn([
            'product' => 'kohevo', 'license_key' => 'correct-key',
            'install_id' => 'uuid-1', 'domain' => 'test.example', 'app_version' => '3.2.1',
        ], '198.51.100.4');

        $checkin = Database::row('SELECT * FROM licensing_checkins WHERE install_id = ?', [$ids['installId']]);
        assert_true($checkin !== null, 'expected a checkins row');
        assert_eq('198.51.100.4', $checkin['ip']);
        assert_eq('test.example', $checkin['reported_domain']);
        assert_eq('active', $checkin['response_status']);

        $install = Database::row('SELECT * FROM licensing_installs WHERE id = ?', [$ids['installId']]);
        assert_eq('198.51.100.4', $install['last_checkin_ip']);
        assert_eq('3.2.1', $install['installed_version']);
        assert_true($install['last_checkin_at'] !== null);
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): a wrong license key and a wrong product slug return the IDENTICAL error -- no enumeration signal', function () {
    licchk_setup();
    try {
        $wrongKey = LicensingAPI::handleCheckIn([
            'product' => 'kohevo', 'license_key' => 'totally-wrong-key', 'install_id' => 'uuid-1',
        ], '203.0.113.9');
        $wrongProduct = LicensingAPI::handleCheckIn([
            'product' => 'nonexistent-product', 'license_key' => 'correct-key', 'install_id' => 'uuid-1',
        ], '203.0.113.9');

        assert_eq($wrongKey['http_status'], $wrongProduct['http_status']);
        assert_eq($wrongKey['body'], $wrongProduct['body']);
        assert_eq(404, $wrongKey['http_status']);
        assert_eq(['error' => 'invalid_request'], $wrongKey['body']);
        assert_false(isset($wrongKey['body']['payload']), 'an error response must never carry a payload/signature');
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): a wrong key does not write a checkins row (nothing to attach it to)', function () {
    $ids = licchk_setup();
    try {
        LicensingAPI::handleCheckIn(['product' => 'kohevo', 'license_key' => 'wrong', 'install_id' => 'uuid-1'], '203.0.113.9');
        $count = (int) Database::value('SELECT COUNT(*) FROM licensing_checkins WHERE install_id = ?', [$ids['installId']]);
        assert_eq(0, $count);
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): a missing required field is rejected with 400, before any lookup happens', function () {
    licchk_setup();
    try {
        $result = LicensingAPI::handleCheckIn(['product' => 'kohevo'], '203.0.113.9');
        assert_eq(400, $result['http_status']);
        assert_eq(['error' => 'invalid_request'], $result['body']);
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): an install past its expires_at reports "expired" even though the stored status is still "active" (lazy expiry)', function () {
    $ids = licchk_setup();
    try {
        Database::update('licensing_installs', ['expires_at' => '2020-01-01 00:00:00'], 'id = ?', [$ids['installId']]);

        $result = LicensingAPI::handleCheckIn(['product' => 'kohevo', 'license_key' => 'correct-key', 'install_id' => 'uuid-1'], '203.0.113.9');
        $decoded = json_decode($result['body']['payload'], true);
        assert_eq('expired', $decoded['status']);

        $stillStored = Database::value('SELECT status FROM licensing_installs WHERE id = ?', [$ids['installId']]);
        assert_eq('active', $stillStored, 'the stored status column is untouched -- expiry is reported lazily, swept later');
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): an install with no plan assigned reports plan=null and entitlements=[]', function () {
    $productId = null;
    $prop = new ReflectionProperty(LicensingAPI::class, 'schemaChecked');
    $prop->setValue(null, false);
    LicensingAPI::ensureSchema();
    LicensingAPI::storeSigningKeypair(LicensingAPI::generateSigningKeypair());
    try {
        $productId = Database::insert('licensing_products', ['slug' => 'kohevo', 'name' => 'Kohevo']);
        $clientId  = Database::insert('licensing_clients', ['name' => 'Test Client']);
        Database::insert('licensing_installs', [
            'client_id' => $clientId, 'product_id' => $productId, 'plan_id' => null,
            'domain' => 'noplan.example', 'license_key_hash' => hash('sha256', 'noplan-key'),
            'status' => 'trial',
        ]);

        $result = LicensingAPI::handleCheckIn(['product' => 'kohevo', 'license_key' => 'noplan-key', 'install_id' => 'uuid-2'], '203.0.113.9');
        $decoded = json_decode($result['body']['payload'], true);
        assert_null($decoded['plan']);
        assert_eq([], $decoded['entitlements']);
        assert_eq('trial', $decoded['status']);
    } finally {
        licchk_teardown();
    }
});

unit('handleCheckIn(): fails closed with a generic server_error when no signing keypair is provisioned yet', function () {
    $prop = new ReflectionProperty(LicensingAPI::class, 'schemaChecked');
    $prop->setValue(null, false);
    LicensingAPI::ensureSchema();
    try {
        $productId = Database::insert('licensing_products', ['slug' => 'kohevo', 'name' => 'Kohevo']);
        $clientId  = Database::insert('licensing_clients', ['name' => 'Test Client']);
        Database::insert('licensing_installs', [
            'client_id' => $clientId, 'product_id' => $productId,
            'domain' => 'nokey.example', 'license_key_hash' => hash('sha256', 'nokey-key'),
            'status' => 'active',
        ]);

        $result = LicensingAPI::handleCheckIn(['product' => 'kohevo', 'license_key' => 'nokey-key', 'install_id' => 'uuid-3'], '203.0.113.9');
        assert_eq(500, $result['http_status']);
        assert_eq(['error' => 'server_error'], $result['body']);
    } finally {
        licchk_teardown();
    }
});
