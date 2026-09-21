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

    // ── Phase 2: Ed25519 signing ────────────────────────────────
    //
    // Every response this server sends a client install must be verifiable
    // with ONLY the public key — the private key never leaves this server,
    // and client code (see client/LicenseSignatureVerifier.php) never
    // touches it. sign()/verify() are pure functions with no Slate
    // dependency beyond ext-sodium, so the exact same call also works
    // inside that standalone client file.

    /**
     * Generate a new Ed25519 keypair. Returns raw (not yet stored) keys as
     * base64 strings — the caller decides how/whether to persist them.
     * Never logs or echoes the secret key itself.
     */
    public static function generateSigningKeypair(): array {
        $pair = sodium_crypto_sign_keypair();
        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'secret' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    /** Sign an arbitrary payload with a base64-encoded Ed25519 secret key. */
    public static function sign(string $payload, string $secretKeyB64): string {
        $secretKey = base64_decode($secretKeyB64, true);
        if ($secretKey === false) {
            throw new \InvalidArgumentException('Malformed secret key.');
        }
        return base64_encode(sodium_crypto_sign_detached($payload, $secretKey));
    }

    /** Verify a payload's signature with ONLY a base64-encoded public key. */
    public static function verify(string $payload, string $signatureB64, string $publicKeyB64): bool {
        $signature = base64_decode($signatureB64, true);
        $publicKey = base64_decode($publicKeyB64, true);
        if ($signature === false || $publicKey === false) {
            return false;
        }
        try {
            return sodium_crypto_sign_verify_detached($signature, $payload, $publicKey);
        } catch (\SodiumException $e) {
            return false; // malformed signature/key length — never a fatal
        }
    }

    // ── Key storage ──────────────────────────────────────────────
    //
    // Public key: stored as plain text — it's meant to be embedded in every
    // client install, there is nothing to protect. Secret key: stored
    // through slate_encrypt_secret() (AES-256-GCM keyed off APP_SECRET),
    // the same at-rest encryption already used for Stripe/Twilio/SMTP
    // credentials elsewhere in this app — never persisted in plain text.

    private const SETTING_PUBLIC_KEY = 'licensing.signing_public_key';
    private const SETTING_SECRET_KEY = 'licensing.signing_secret_key';

    public static function hasSigningKeypair(): bool {
        return (string) Database::setting(self::SETTING_PUBLIC_KEY) !== '';
    }

    public static function storeSigningKeypair(array $keypair): void {
        Database::setSetting(self::SETTING_PUBLIC_KEY, $keypair['public']);
        Database::setSetting(self::SETTING_SECRET_KEY, slate_encrypt_secret($keypair['secret']));
    }

    public static function signingPublicKey(): ?string {
        $val = (string) Database::setting(self::SETTING_PUBLIC_KEY);
        return $val !== '' ? $val : null;
    }

    public static function signingSecretKey(): ?string {
        $raw = (string) Database::setting(self::SETTING_SECRET_KEY);
        if ($raw === '') return null;
        return slate_decrypt_secret($raw);
    }

    // ── Phase 3: the check-in endpoint ────────────────────────────
    //
    // Business logic lives here, not in public/check.php, so it can be
    // tested directly (a real request/response cycle) without needing a
    // live HTTP server. public/check.php is a thin shim: read the body,
    // call handleCheckIn(), emit the status + JSON it returns.
    //
    // Response shape is deliberately a signed ENVELOPE, not a flat signed
    // object: {"payload": "<exact JSON string that was signed>",
    // "signature": "..."}. `payload` is a JSON string, not a nested
    // object — the client verifies the signature against that exact byte
    // string first, and only decodes it into fields after verification
    // succeeds. A flat object would force the client to re-serialize the
    // fields identically to verify, which is exactly the kind of
    // canonicalization mismatch that quietly breaks signature schemes.
    //
    // An invalid product slug and an invalid license key return the
    // IDENTICAL error (same HTTP status, same body) — matching this
    // app's existing "unknown email vs. wrong password fail identically"
    // anti-enumeration precedent (Auth::attemptLogin()). A caller must
    // never be able to tell "wrong key" from "wrong product" from the
    // response alone.

    public static function handleCheckIn(array $input, string $ip): array {
        try {
            $productSlug = trim((string) ($input['product'] ?? ''));
            $licenseKey  = (string) ($input['license_key'] ?? '');
            $installId   = trim((string) ($input['install_id'] ?? ''));
            $domain      = trim((string) ($input['domain'] ?? ''));
            $appVersion  = trim((string) ($input['app_version'] ?? ''));

            if ($productSlug === '' || $licenseKey === '' || $installId === '') {
                return self::checkInError(400, 'invalid_request');
            }

            $product = Database::row('SELECT id FROM licensing_products WHERE slug = ?', [$productSlug]);
            if (!$product) {
                return self::checkInError(404, 'invalid_request');
            }

            $keyHash = hash('sha256', $licenseKey);
            $install = Database::row(
                'SELECT * FROM licensing_installs WHERE product_id = ? AND license_key_hash = ?',
                [$product['id'], $keyHash]
            );
            if (!$install) {
                return self::checkInError(404, 'invalid_request');
            }

            $status = self::effectiveInstallStatus($install);

            Database::insert('licensing_checkins', [
                'install_id'       => $install['id'],
                'ip'               => $ip !== '' ? $ip : null,
                'reported_domain'  => $domain !== '' ? $domain : null,
                'reported_version' => $appVersion !== '' ? $appVersion : null,
                'response_status'  => $status,
            ]);
            Database::update('licensing_installs', [
                'last_checkin_at'   => slate_db_now(),
                'last_checkin_ip'   => $ip !== '' ? $ip : null,
                'installed_version' => $appVersion !== '' ? $appVersion : $install['installed_version'],
            ], 'id = ?', [$install['id']]);

            $planSlug     = null;
            $entitlements = [];
            if (!empty($install['plan_id'])) {
                $plan = Database::row('SELECT slug, entitlements_json FROM licensing_plans WHERE id = ?', [$install['plan_id']]);
                if ($plan) {
                    $planSlug     = $plan['slug'];
                    $entitlements = json_decode((string) $plan['entitlements_json'], true) ?: [];
                }
            }

            $secretKey = self::signingSecretKey();
            if ($secretKey === null) {
                // Server has no keypair provisioned yet — fail closed, but
                // the caller sees the same generic error as any other
                // failure, not "the server isn't set up".
                slate_log('Licensing check-in: no signing keypair provisioned', 'error');
                return self::checkInError(500, 'server_error');
            }

            $payload = [
                'status'            => $status,
                'plan'              => $planSlug,
                'entitlements'      => $entitlements,
                'expires_at'        => $install['expires_at'],
                'checked_at'        => gmdate('c'),
                'next_check_after'  => 86400,
            ];
            $payloadJson = (string) json_encode($payload, JSON_UNESCAPED_SLASHES);
            $signature   = self::sign($payloadJson, $secretKey);

            return ['http_status' => 200, 'body' => ['payload' => $payloadJson, 'signature' => $signature]];
        } catch (\Throwable $e) {
            slate_log('Licensing check-in failed: ' . $e->getMessage(), 'error');
            return self::checkInError(500, 'server_error');
        }
    }

    /** Lazy expiry, same reasoning as the local LicenseService::effectiveStatus(): expires_at
     *  in the past reports 'expired' immediately, without waiting for a sweep to persist it. */
    private static function effectiveInstallStatus(array $install): string {
        $status = (string) ($install['status'] ?? 'trial');
        if (in_array($status, ['trial', 'active'], true) && !empty($install['expires_at'])
            && strtotime((string) $install['expires_at']) <= time()) {
            return 'expired';
        }
        return $status;
    }

    private static function checkInError(int $httpStatus, string $code): array {
        return ['http_status' => $httpStatus, 'body' => ['error' => $code]];
    }

    // ── Admin UI support: issuing and rotating license keys ────────
    //
    // The raw key is generated here and handed back to the caller exactly
    // once — only its SHA-256 hash is ever persisted (same rule the
    // install.sql header states for `licensing_installs.license_key_hash`).
    // Centralized here rather than in admin/installs.php so the admin
    // screen and any future CLI/API path generate keys the same way.

    /** A readable, high-entropy key: PRODUCTSLUG-XXXX-XXXX-XXXX-XXXX-XXXX. */
    public static function generateLicenseKey(string $productSlug): string {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productSlug) ?? '', 0, 12));
        if ($prefix === '') $prefix = 'LIC';
        $raw = strtoupper(bin2hex(random_bytes(10)));
        return $prefix . '-' . implode('-', str_split($raw, 4));
    }

    /**
     * Create a new install/license row. $data keys: client_id, product_id,
     * plan_id (nullable), label, domain, status, expires_at (nullable),
     * activation_limit. Returns ['id' => int, 'license_key' => string] —
     * the caller must show license_key to the admin now; it cannot be
     * retrieved again afterward.
     */
    public static function issueInstall(array $data, string $productSlug): array {
        $licenseKey = self::generateLicenseKey($productSlug);
        $id = Database::insert('licensing_installs', [
            'client_id'        => (int) $data['client_id'],
            'product_id'       => (int) $data['product_id'],
            'plan_id'          => !empty($data['plan_id']) ? (int) $data['plan_id'] : null,
            'label'            => (string) $data['label'],
            'domain'           => (string) $data['domain'],
            'license_key_hash' => hash('sha256', $licenseKey),
            'status'           => (string) $data['status'],
            'expires_at'       => $data['expires_at'] !== '' ? $data['expires_at'] : null,
            'activation_limit' => max(1, (int) $data['activation_limit']),
        ]);
        return ['id' => $id, 'license_key' => $licenseKey];
    }

    /** Rotate an existing install's key. Old key stops working immediately. */
    public static function regenerateLicenseKey(int $installId, string $productSlug): string {
        $licenseKey = self::generateLicenseKey($productSlug);
        Database::update('licensing_installs',
            ['license_key_hash' => hash('sha256', $licenseKey)],
            'id = ?', [$installId]
        );
        return $licenseKey;
    }
}
