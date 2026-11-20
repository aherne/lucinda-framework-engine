<?php

namespace Lucinda\Framework\Persistence;

use Lucinda\Framework\NoSQL\AtomicDriver;
use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\Framework\NoSQL\Keyspace;

/**
 * Executes opaque record operations through the configured NoSQL adapter
 *
 * The listener owns XML mapping and supplies the atomic driver explicitly.
 * This executor contains no vendor selection or native-client access.
 * Session, migration and throttle policy remain outside execution.
 *
 * @internal
 */
final class NoSqlExecutor
{
    /** Atomic driver supplied by application setup */
    private AtomicDriver $driver;

    /** Namespace shared by stores using this executor */
    private Keyspace $keyspace;

    /**
     * Captures the injected adapter and application namespace
     *
     * @param AtomicDriver $driver Explicit atomic driver supplied at setup
     * @param string|null $prefix Key prefix or PERSISTENCE_NAMESPACE setting
     */
    public function __construct(
        AtomicDriver $driver,
        ?string $prefix = null
    ) {
        $this->driver = $driver;
        if ($prefix === null) {
            $prefix = getenv('PERSISTENCE_NAMESPACE');
            if ($prefix === false || $prefix === '') {
                $prefix = 'framework:';
            }
        }
        $this->keyspace = new Keyspace($prefix);
    }

    /**
     * Gets the namespace used by all stores sharing this executor
     *
     * @return Keyspace Application-specific key namespace
     */
    public function getKeyspace(): Keyspace
    {
        return $this->keyspace;
    }

    /**
     * Reads one opaque record without interpreting domain fields
     *
     * @param string $key Fully namespaced record identity
     * @return VersionedValue Immutable observation for a conditional write
     * @throws \Throwable If the backend cannot read the record
     */
    public function read(string $key): VersionedValue
    {
        $result = $this->driver->getVersioned($key);
        if ($result->exists() && !is_array($result->getValue())) {
            throw new \UnexpectedValueException('Expected a NoSQL record');
        }
        return $result;
    }

    /**
     * Conditionally replaces one previously observed record
     *
     * @param VersionedValue $snapshot Expected current record
     * @param array<string, mixed> $values Replacement record fields
     * @param int $lifetime Retention in seconds, zero for no expiration
     * @return bool True when accepted, false when the caller must retry
     * @throws \InvalidArgumentException If retention is negative
     * @throws \Throwable If validation or storage fails
     */
    public function replace(
        VersionedValue $snapshot,
        array $values,
        int $lifetime = 0
    ): bool {
        if ($lifetime < 0) {
            throw new \InvalidArgumentException('Negative record lifetime');
        }
        return $this->driver->compareAndSwap(
            $snapshot,
            $values,
            $lifetime
        );
    }

    /**
     * Deletes only the exact record observed by the caller
     *
     * @param VersionedValue $snapshot Existing record expected by its owner
     * @return bool True when deleted, false when absent, expired or stale
     * @throws \Throwable If snapshot validation or storage fails
     */
    public function remove(VersionedValue $snapshot): bool
    {
        return $this->driver->compareAndDelete($snapshot);
    }
}
