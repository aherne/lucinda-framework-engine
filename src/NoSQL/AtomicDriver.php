<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\NoSQL\Driver;
use Lucinda\NoSQL\OperationFailedException;

/**
 * Adds versioned atomic operations to the ordinary key-value driver contract
 *
 * An adapter owns a reserved namespace. Every mutation through that adapter,
 * including inherited operations, invalidates previously observed versions.
 * Backend failures throw; a stale conditional operation returns false.
 */
interface AtomicDriver extends Driver
{
    /**
     * Captures a value and its version without changing its expiration
     *
     * @param string $key Logical key inside the adapter's namespace
     * @return VersionedValue Existing value or an explicit absent observation
     * @throws OperationFailedException If storage or record decoding fails
     */
    public function getVersioned(string $key): VersionedValue;

    /**
     * Creates or replaces a value only while its observation still matches
     *
     * @param VersionedValue $expected Observation issued by this adapter
     * @param mixed $value Scalar, null or array of supported values
     * @param int $expiration Seconds from the write; zero means no expiration
     * @return bool True when written, false when the observation is stale
     * @throws \InvalidArgumentException If arguments or origin are invalid
     * @throws OperationFailedException If storage fails
     */
    public function compareAndSwap(
        VersionedValue $expected,
        mixed $value,
        int $expiration
    ): bool;

    /**
     * Deletes an existing value only while its observed version still matches
     *
     * @param VersionedValue $expected Observation issued by this adapter
     * @return bool True when deleted, false when absent, expired or stale
     * @throws \InvalidArgumentException If another adapter issued the snapshot
     * @throws OperationFailedException If storage fails
     */
    public function compareAndDelete(VersionedValue $expected): bool;
}
