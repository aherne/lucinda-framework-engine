<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\NoSQL\Driver;
use Lucinda\NoSQL\KeyNotFoundException;
use Lucinda\NoSQL\OperationFailedException;

/**
 * Coordinates logical versions and inherited operations for atomic adapters
 *
 * Native subclasses implement only observation and conditional replacement.
 * This instance owns its wrapped driver's lifetime, reserved key namespace
 * and weak association between public snapshots and native observations.
 */
abstract class AbstractAtomicDriver implements AtomicDriver
{
    /** Wrapped library driver retained while its native connection is used */
    private Driver $driver;

    /** Reserved prefix separating atomic records from ordinary library keys */
    private string $namespace;

    /** @var \WeakMap<VersionedValue, StorageObservation> Issued observations */
    private \WeakMap $observations;

    /**
     * Captures the original driver and a reserved namespace
     *
     * @param Driver $driver Existing unmodified library driver
     * @param string $namespace Nonempty physical key prefix
     * @throws \InvalidArgumentException If the namespace is empty
     */
    protected function __construct(Driver $driver, string $namespace)
    {
        if ($namespace === '') {
            throw new \InvalidArgumentException('Atomic namespace is empty');
        }
        $this->driver = $driver;
        $this->namespace = $namespace;
        $this->observations = new \WeakMap();
    }

    /**
     * Observes a physical record using the subclass's native backend
     *
     * @param string $key Physical namespaced key
     * @return StorageObservation Bytes and native comparison metadata
     * @throws OperationFailedException If the native read fails
     */
    abstract protected function readStorage(string $key): StorageObservation;

    /**
     * Replaces a physical record only while its native observation matches
     *
     * @param StorageObservation $expected Native observation to compare
     * @param string $value Complete encoded replacement
     * @param int $expiration Native retention in seconds, zero for no expiry
     * @return bool True when written, false for native contention
     * @throws OperationFailedException If storage fails
     */
    abstract protected function replaceStorage(
        StorageObservation $expected,
        string $value,
        int $expiration
    ): bool;

    /**
     * Captures a live value or an explicit absent logical observation
     *
     * @param string $key Logical identity inside the reserved namespace
     * @return VersionedValue Observation owned by this adapter instance
     * @throws \InvalidArgumentException If the logical key is empty
     * @throws OperationFailedException If storage or record decoding fails
     */
    final public function getVersioned(string $key): VersionedValue
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Atomic key is empty');
        }
        $stored = $this->readStorage($this->namespace . $key);
        $record = null;
        if ($stored->getValue() !== null) {
            $builder = new StoredValueBuilder($stored->getValue());
            $record = $builder->build();
        }
        if ($record !== null && $record->isLive()) {
            $result = new VersionedValue(
                $key,
                $record->getValue(),
                $record->getVersion(),
                $record->getExpiresAt()
            );
        } else {
            $result = new VersionedValue($key, null, null, 0.0);
        }
        $this->observations[$result] = $stored;
        return $result;
    }

    /**
     * Requires the exact observation issued by this adapter
     *
     * @param VersionedValue $expected Public observation supplied by a caller
     * @return StorageObservation Associated native observation
     * @throws \InvalidArgumentException If the snapshot has another origin
     */
    private function getObservation(
        VersionedValue $expected
    ): StorageObservation {
        if (!isset($this->observations[$expected])) {
            throw new \InvalidArgumentException('Foreign atomic observation');
        }
        return $this->observations[$expected];
    }

    /**
     * Publishes a new logical revision after checking snapshot ownership
     *
     * @param VersionedValue $expected Observation to compare
     * @param mixed $value Supported replacement payload
     * @param float $expiresAt Logical expiration or zero
     * @param int $retention Native retention in seconds
     * @param bool $deleted Whether this is a deletion tombstone
     * @return bool True when published, false for an expired or stale snapshot
     * @throws \InvalidArgumentException If arguments or origin are invalid
     * @throws OperationFailedException If storage fails
     */
    private function writeExpected(
        VersionedValue $expected,
        mixed $value,
        float $expiresAt,
        int $retention,
        bool $deleted = false
    ): bool {
        $observation = $this->getObservation($expected);
        if (
            $expected->exists() && $expected->getExpiresAt() !== 0.0
            && $expected->getExpiresAt() <= microtime(true)
        ) {
            return false;
        }
        $record = new StoredValue(
            bin2hex(random_bytes(24)),
            $expiresAt,
            $value,
            $deleted
        );
        return $this->replaceStorage(
            $observation,
            $record->encode(),
            $retention
        );
    }

    /**
     * Atomically creates an absent key or replaces the observed live version
     *
     * @param VersionedValue $expected Observation issued by this adapter
     * @param mixed $value Scalar, null or array of supported values
     * @param int $expiration Seconds from the write, zero for no expiration
     * @return bool True when written, false when the observation is stale
     * @throws \InvalidArgumentException If arguments or origin are invalid
     * @throws OperationFailedException If storage fails
     */
    final public function compareAndSwap(
        VersionedValue $expected,
        mixed $value,
        int $expiration
    ): bool {
        if ($expiration < 0) {
            throw new \InvalidArgumentException('Negative atomic expiration');
        }
        $expiresAt = $expiration === 0 ? 0.0 : microtime(true) + $expiration;
        return $this->writeExpected(
            $expected,
            $value,
            $expiresAt,
            $expiration
        );
    }

    /**
     * Atomically deletes the exact existing version supplied by a caller
     *
     * Tombstones provide compare-and-delete on backends that expose only CAS.
     * They are logically absent immediately and have short native retention.
     *
     * @param VersionedValue $expected Observation issued by this adapter
     * @return bool True when deleted, false when absent, expired or stale
     * @throws \InvalidArgumentException If the snapshot has another origin
     * @throws OperationFailedException If storage fails
     */
    final public function compareAndDelete(VersionedValue $expected): bool
    {
        $this->getObservation($expected);
        if (!$expected->exists()) {
            return false;
        }
        return $this->writeExpected(
            $expected,
            null,
            microtime(true) + 1,
            1,
            true
        );
    }

    /**
     * Writes a value while invalidating every previously observed revision
     *
     * @param string $key Logical storage identity
     * @param mixed $value Scalar, null or array of supported values
     * @param int $expiration Lifetime in seconds, zero for no expiration
     * @throws \InvalidArgumentException If the value or lifetime is invalid
     * @throws OperationFailedException If storage fails or contention persists
     */
    final public function set(
        string $key,
        mixed $value,
        int $expiration = 0
    ): void {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $expected = $this->getVersioned($key);
            if ($this->compareAndSwap($expected, $value, $expiration)) {
                return;
            }
        }
        throw new OperationFailedException('Atomic write contention limit');
    }

    /**
     * Checks logical existence without extending the stored deadline
     *
     * @param string $key Logical storage identity
     * @return bool True for a live value, including stored null or false
     * @throws OperationFailedException If storage access fails
     */
    final public function contains(string $key): bool
    {
        return $this->getVersioned($key)->exists();
    }

    /**
     * Reads the ordinary value through the same versioned representation
     *
     * @param string $key Logical storage identity
     * @return mixed Stored scalar, null or array value
     * @throws KeyNotFoundException If the key is absent or expired
     * @throws OperationFailedException If storage access fails
     */
    final public function get(string $key): mixed
    {
        return $this->getVersioned($key)->getValue();
    }

    /**
     * Adjusts an existing integer without extending its original expiration
     *
     * @param string $key Logical counter identity
     * @param int $offset Nonnegative adjustment magnitude
     * @param bool $decrement Whether to subtract instead of adding
     * @return int Accepted integer value
     * @throws \InvalidArgumentException If the offset is negative
     * @throws KeyNotFoundException If the counter does not exist
     * @throws OperationFailedException If type, range or storage is invalid
     */
    private function adjust(string $key, int $offset, bool $decrement): int
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Negative counter offset');
        }
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $expected = $this->getVersioned($key);
            $current = $expected->getValue();
            if (!is_int($current)) {
                throw new OperationFailedException('Counter must be integer');
            }
            $next = $decrement ? $current - $offset : $current + $offset;
            if (!is_int($next)) {
                throw new OperationFailedException('Integer counter overflow');
            }
            $expiresAt = $expected->getExpiresAt();
            $retention = $expiresAt === 0.0 ? 0
                : max(1, (int) ceil($expiresAt - microtime(true)));
            if (
                $this->writeExpected(
                    $expected,
                    $next,
                    $expiresAt,
                    $retention
                )
            ) {
                return $next;
            }
        }
        throw new OperationFailedException('Counter contention limit');
    }

    /**
     * Atomically increments an existing integer and invalidates its version
     *
     * @param string $key Logical counter identity
     * @param int $offset Nonnegative increment magnitude
     * @return int Accepted counter value
     * @throws \Throwable If the counter or operation is invalid
     */
    final public function increment(string $key, int $offset = 1): int
    {
        return $this->adjust($key, $offset, false);
    }

    /**
     * Atomically decrements an existing integer and invalidates its version
     *
     * @param string $key Logical counter identity
     * @param int $offset Nonnegative decrement magnitude
     * @return int Accepted signed counter value
     * @throws \Throwable If the counter or operation is invalid
     */
    final public function decrement(string $key, int $offset = 1): int
    {
        return $this->adjust($key, $offset, true);
    }

    /**
     * Deletes an ordinary value through conditional versioned deletion
     *
     * @param string $key Logical storage identity
     * @throws KeyNotFoundException If the key is absent or expired
     * @throws OperationFailedException If storage fails or contention persists
     */
    final public function delete(string $key): void
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $expected = $this->getVersioned($key);
            if (!$expected->exists()) {
                throw new KeyNotFoundException($key);
            }
            if ($this->compareAndDelete($expected)) {
                return;
            }
        }
        throw new OperationFailedException('Atomic delete contention limit');
    }

    /**
     * Preserves the wrapped driver's existing whole-backend flush operation
     *
     * This inherited operation is not restricted to the adapter namespace.
     * Framework persistence and its tests never use it for targeted cleanup.
     *
     * @throws OperationFailedException If the wrapped driver's flush fails
     */
    final public function flush(): void
    {
        $this->driver->flush();
    }
}
