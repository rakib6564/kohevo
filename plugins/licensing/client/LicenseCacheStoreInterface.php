<?php
/**
 * The storage seam for RemoteLicenseClient — deliberately tiny and
 * deliberately dumb. RemoteLicenseClient never touches a database
 * directly; it only ever calls load()/save() on whatever implements
 * this. Each product wires its own adapter against its own storage
 * however fits (Kohevo's is Slate\Services\Licensing\SlateLicenseCacheStore,
 * a ~30-line class over a single DB table) — the HTTP + signature-
 * verification logic in RemoteLicenseClient never changes across products.
 */

declare(strict_types=1);

interface LicenseCacheStoreInterface {

    /**
     * The last successfully verified status, or null if none has ever
     * been stored. Shape: {status, plan, entitlements, expires_at,
     * fetched_at} — see RemoteLicenseClient::checkIn()'s save() call for
     * the exact keys.
     */
    public function load(): ?array;

    /**
     * Persist a newly verified status. Only ever called after a response
     * has been signature-verified — a store implementation never needs to
     * (and never should) second-guess whether the data it's given is
     * trustworthy.
     */
    public function save(array $status): void;
}
