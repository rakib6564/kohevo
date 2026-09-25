# Licensing

**Status:** As built  
**Verified version:** `0.1.0`  
**Owner:** `plugins/licensing`

## Purpose

Licensing provides a small license-server surface for Kohevo products. Administrators manage products, clients, plans, entitlements, install records, and license status. Product installations call a public JSON check-in endpoint and receive an Ed25519-signed response.

## Surface

The public endpoint is `POST /licensing/check`. It accepts a product slug, license key, install ID, optional domain, and application version. It returns a signed payload containing status, plan, entitlements, expiry, check time, and a 24-hour next-check hint.

The admin pages manage products, clients, plans, installs, key rotation, status, expiry, domain, and check-in history. `bin/licensing-generate-keys.php` provisions the signing key pair. The client library includes `RemoteLicenseClient`, `LicenseSignatureVerifier`, and `LicenseCacheStoreInterface`.

## Data and lifecycle

The module owns `licensing_products`, `licensing_clients`, `licensing_plans`, `licensing_installs`, and append-only `licensing_checkins`. License keys are shown once and stored only as SHA-256 hashes. Regeneration invalidates the old key. Expiry is evaluated lazily during check-in.

The server signs the exact response bytes with sodium Ed25519. The private key is encrypted at rest; the public key is stored as a setting. The module uses `licensing.signing_public_key` and encrypted `licensing.signing_secret_key`.

## Permissions and security

Admin pages require `licensing.manage`, authentication, and CSRF verification. The public endpoint has no login requirement; possession of the product slug and license key is the credential. Unknown products or keys produce a generic invalid-request response.

The current implementation does not enforce an activation count, correlate `install_id` with an install record, or verify the reported domain against the stored domain. It also has no explicit rate limit, replay protection, or check-in retention policy. These are product-security gaps, not implicit guarantees.

## Source map

See `Licensing.php`, `LicensingAPI.php`, `public/check.php`, all files under `admin/`, `client/`, `install.sql`, `uninstall.sql`, and `bin/licensing-generate-keys.php`.

## Known gaps

There is no module README or previous canonical page. This page is the authoritative reference until the check-in binding and activation-limit behavior are strengthened.
