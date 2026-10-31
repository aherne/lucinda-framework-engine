<?php

namespace Lucinda\Framework\OAuth2;

/**
 * Persists browser-bound OAuth2 authorization attempts in chosen storage medium
 *
 * Stores only the browser proof's SHA-256 hash. Consumption uses a conditional
 * DELETE and its affected-row count so concurrent callbacks cannot both accept
 * the same attempt. The configured connection must use autocommit, and
 * these operations must run outside application transactions.
 *
 * @see \Lucinda\Framework\OAuth2\State
 */
interface StateStorage
{
    /**
     * Inserts one authorization attempt without replacing an existing state
     *
     * @param string $state Full random state sent to the OAuth2 provider
     * @param string $vendorName Configured provider name bound to the attempt
     * @param int $validUntil Absolute expiration timestamp in Unix seconds
     * @param string $browserProofHash Hexadecimal SHA-256 hash of the browser proof
     * @throws \Exception If insertion or database access fails
     */
    public function save(string $state, string $vendorName, int $validUntil, string $browserProofHash): void;

    /**
     * Atomically accepts and deletes a live attempt matching its browser proof
     *
     * A successful deletion accepts the attempt exactly once. A rejected
     * callback also removes an expired entry belonging to the same browser,
     * but never deletes a live entry with a mismatched provider or proof.
     *
     * @param string $state State received from the provider callback
     * @param string $vendorName Configured provider handling the callback
     * @param string $browserProofHash Hexadecimal SHA-256 hash of the received cookie proof
     * @return bool True only when exactly one live matching row was deleted
     * @throws \Exception If deletion or database access fails
     */
    public function consume(string $state, string $vendorName, string $browserProofHash): bool;

    /**
     * Deletes expired attempts, including abandoned browser login flows
     *
     * Call periodically from application maintenance rather than relying on
     * callbacks to visit every expired attempt.
     *
     * @throws \Exception If deletion or database access fails
     */
    public function clearExpired(): void;
}
