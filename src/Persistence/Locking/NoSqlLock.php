<?php

namespace Lucinda\Framework\Persistence\Locking;

use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\NoSqlExecutor;

/**
 * Owns an expiring NoSQL lease and guards changes to its protected record
 *
 * The reserved token and lease_expires fields share one atomic record with
 * optional application fields. Replacements compare that complete record so
 * a previous owner cannot overwrite a successor. A standalone lease has no
 * application fields and is conditionally deleted on release.
 *
 * All owners must use the same backend and keyspace. APCu coordinates only
 * its local cache domain. Expiry, eviction and failover can end ownership;
 * this lease alone cannot fence writes to a separate storage system.
 *
 * @internal
 */
final class NoSqlLock implements Lock
{
    /** Executor providing atomic access to the protected record */
    private NoSqlExecutor $executor;

    /** Keyspace purpose shared by all contenders for this record */
    private string $purpose;

    /** Lifetime in seconds of an acquired or renewed lease */
    private int $leaseLifetime;

    /** Maximum acquisition wait in seconds; zero makes one attempt */
    private int $waitTimeout;

    /** Fully namespaced identity owned during the current lifecycle */
    private ?string $key = null;

    /** Unique owner token retained until explicit release */
    private ?string $token = null;

    /**
     * Captures storage and the lease policy for one record domain
     *
     * @param NoSqlExecutor $executor Shared atomic connection and namespace
     * @param string $purpose Storage domain, such as lock or session
     * @param int $leaseLifetime Positive ownership lifetime in seconds
     * @param int $waitTimeout Nonnegative acquisition timeout in seconds
     * @throws \InvalidArgumentException If the domain or policy is invalid
     */
    public function __construct(
        NoSqlExecutor $executor,
        string $purpose = 'lock',
        int $leaseLifetime = 60,
        int $waitTimeout = 10
    ) {
        if ($purpose === '' || $leaseLifetime < 1 || $waitTimeout < 0) {
            throw new \InvalidArgumentException('Invalid NoSQL lock policy');
        }
        $this->executor = $executor;
        $this->purpose = $purpose;
        $this->leaseLifetime = $leaseLifetime;
        $this->waitTimeout = $waitTimeout;
    }

    /**
     * Reads lease metadata while preserving protected application fields
     *
     * @param VersionedValue $snapshot Observation from this lock's storage
     * @return array<string, mixed> Record with validated lease metadata
     * @throws \UnexpectedValueException If existing lease fields are invalid
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If a lease column has an incompatible type
     */
    private function getRecord(VersionedValue $snapshot): array
    {
        $values = $snapshot->exists() ? $snapshot->getValue() : [];
        if (
            !array_key_exists('token', $values)
            && !array_key_exists('lease_expires', $values)
        ) {
            $values['token'] = null;
            $values['lease_expires'] = 0;
        }
        $row = new Row($values, new ParametersValidator());
        $token = $row->getString('token');
        $expires = $row->getInteger('lease_expires')
            ?? throw new \UnexpectedValueException('Null lease expiry');
        if ($token === '' || $expires < 0) {
            throw new \UnexpectedValueException('Invalid NoSQL lease record');
        }
        $values['token'] = $token;
        $values['lease_expires'] = $expires;
        return $values;
    }

    /**
     * Preserves existing retention while keeping a new lease record alive
     *
     * @param VersionedValue $snapshot Previous record and retention deadline
     * @param int $expires Lease deadline, or zero when releasing ownership
     * @return int Retention in seconds; zero preserves unlimited retention
     */
    private function getLifetime(VersionedValue $snapshot, int $expires): int
    {
        if ($snapshot->exists() && $snapshot->getExpiresAt() === 0.0) {
            return 0;
        }
        return max(1, (int) ceil(
            max($snapshot->getExpiresAt(), $expires) - microtime(true)
        ));
    }

    /**
     * Requires the current record to belong to this still-live lease
     *
     * @param VersionedValue $snapshot Exact record for a conditional write
     * @return array<string, mixed> Validated record owned by this instance
     * @throws \LogicException If this instance has acquired no identity
     * @throws \RuntimeException If expiry or another owner ended the lease
     */
    private function requireOwnership(VersionedValue $snapshot): array
    {
        if ($this->key === null || $this->token === null) {
            throw new \LogicException('No NoSQL lock has been acquired');
        }
        $record = $this->getRecord($snapshot);
        if (
            $snapshot->getKey() !== $this->key
            || $record['token'] !== $this->token
            || $record['lease_expires'] <= time()
        ) {
            throw new \RuntimeException('NoSQL lock ownership expired or lost');
        }
        return $record;
    }

    /**
     * Acquires a lease without silently reacquiring an expired lifecycle
     *
     * Repeated acquisition validates ownership without renewing the lease.
     * Explicit release is required before starting a replacement lifecycle.
     *
     * @param string $identity Identity within the configured storage domain
     * @throws \RuntimeException If acquisition times out or ownership is lost
     * @throws \Throwable If record validation or storage access fails
     */
    public function acquire(string $identity): void
    {
        $key = $this->executor->getKeyspace()->getKey(
            $this->purpose,
            $identity
        );
        if ($key === $this->key && $this->token !== null) {
            $this->read();
            return;
        }
        $this->release();
        $token = bin2hex(random_bytes(24));
        $deadline = hrtime(true) / 1000000000 + $this->waitTimeout;
        do {
            $snapshot = $this->executor->read($key);
            $record = $this->getRecord($snapshot);
            $now = time();
            if ($record['token'] === null || $record['lease_expires'] <= $now) {
                $record['token'] = $token;
                $record['lease_expires'] = $now + $this->leaseLifetime;
                if (
                    $this->executor->replace(
                        $snapshot,
                        $record,
                        $this->getLifetime($snapshot, $record['lease_expires'])
                    )
                ) {
                    $this->key = $key;
                    $this->token = $token;
                    return;
                }
            }
            $remaining = $deadline - hrtime(true) / 1000000000;
            if ($remaining <= 0) {
                break;
            }
            usleep(max(1, (int) min(20000, $remaining * 1000000)));
        } while (hrtime(true) / 1000000000 < $deadline);
        throw new \RuntimeException('NoSQL lock acquisition timed out');
    }

    /**
     * Observes the protected record and verifies current lease ownership
     *
     * @return VersionedValue Observation for a protected replacement
     * @throws \LogicException If this instance has acquired no identity
     * @throws \Throwable If ownership validation or storage access fails
     */
    public function read(): VersionedValue
    {
        if ($this->key === null) {
            throw new \LogicException('No NoSQL lock has been acquired');
        }
        $snapshot = $this->executor->read($this->key);
        $this->requireOwnership($snapshot);
        return $snapshot;
    }

    /**
     * Conditionally replaces protected fields without changing the lease
     *
     * Ownership and payload share the compared record. A false result requires
     * a fresh read and validation, never an unconditional fallback write.
     *
     * @param VersionedValue $snapshot Observation returned by read()
     * @param array<string, mixed> $values Replacement record fields
     * @param int $lifetime Retention in seconds; zero means no expiration
     * @return bool True when accepted, false when the observation is stale
     * @throws \Throwable If ownership, retention or storage is invalid
     */
    public function replace(
        VersionedValue $snapshot,
        array $values,
        int $lifetime
    ): bool {
        $record = $this->requireOwnership($snapshot);
        $values['token'] = $record['token'];
        $values['lease_expires'] = $record['lease_expires'];
        if ($lifetime > 0) {
            $lifetime = max($lifetime, $record['lease_expires'] - time());
        }
        return $this->executor->replace($snapshot, $values, $lifetime);
    }

    /**
     * Extends a still-owned lease without discarding protected fields
     *
     * @throws \RuntimeException If ownership was lost or contention persists
     * @throws \Throwable If validation or storage access fails
     */
    public function renew(): void
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->read();
            $record = $this->requireOwnership($snapshot);
            $record['lease_expires'] = time() + $this->leaseLifetime;
            if (
                $this->executor->replace(
                    $snapshot,
                    $record,
                    $this->getLifetime($snapshot, $record['lease_expires'])
                )
            ) {
                return;
            }
        }
        throw new \RuntimeException('NoSQL lock renewal contention limit');
    }

    /**
     * Releases only this owner and preserves protected application fields
     *
     * A stale release is harmless. Failed storage access retains local state
     * so the caller can retry; the finite lease also permits crash recovery.
     *
     * @throws \RuntimeException If contention prevents release
     * @throws \Throwable If validation or storage access fails
     */
    public function release(): void
    {
        if ($this->key === null || $this->token === null) {
            return;
        }
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->executor->read($this->key);
            $record = $this->getRecord($snapshot);
            if ($record['token'] !== $this->token) {
                $this->key = null;
                $this->token = null;
                return;
            }
            if (count($record) === 2) {
                $accepted = $this->executor->remove($snapshot);
            } else {
                $record['token'] = null;
                $record['lease_expires'] = 0;
                $accepted = $this->executor->replace(
                    $snapshot,
                    $record,
                    $this->getLifetime($snapshot, 0)
                );
            }
            if ($accepted) {
                $this->key = null;
                $this->token = null;
                return;
            }
        }
        throw new \RuntimeException('NoSQL lock release contention limit');
    }

    /**
     * Attempts release before the lifecycle leaves scope
     */
    public function __destruct()
    {
        try {
            $this->release();
        } catch (\Throwable) {
            // Lease expiration remains the recovery path after shutdown.
        }
    }
}
