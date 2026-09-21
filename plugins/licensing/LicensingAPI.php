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
}
