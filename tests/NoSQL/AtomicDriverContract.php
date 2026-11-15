<?php

namespace Test\Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\AtomicDriver;
use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\NoSQL\KeyNotFoundException;
use Lucinda\NoSQL\OperationFailedException;

/**
 * Runs the same observable atomic-driver contract against real vendor adapters
 *
 * Tests reserve unique logical keys and never flush a backend. Two adapter
 * instances verify that snapshots cannot be transferred across owners.
 */
final class AtomicDriverContract
{
    /** Adapter whose ordinary and conditional operations are exercised */
    private AtomicDriver $driver;

    /** Separate adapter observing the same backend and key namespace */
    private AtomicDriver $other;

    /** Unique prefix reserved by the integration-test runner */
    private string $prefix;

    /** Number of assertions completed by this contract instance */
    private int $count = 0;

    /** @var list<string> Logical keys reserved for targeted test cleanup */
    private array $keys = [];

    /**
     * Captures two real adapters and their isolated test namespace
     *
     * @param AtomicDriver $driver Adapter under test
     * @param AtomicDriver $other Independent adapter for origin checks
     * @param string $prefix Unique test-only key prefix
     */
    public function __construct(
        AtomicDriver $driver,
        AtomicDriver $other,
        string $prefix
    ) {
        $this->driver = $driver;
        $this->other = $other;
        $this->prefix = $prefix;
    }

    /**
     * Requires an observable contract invariant
     *
     * @param bool $condition Observed assertion result
     * @param string $message Failure description
     * @throws \RuntimeException If the invariant fails
     */
    private function check(bool $condition, string $message): void
    {
        $this->count++;
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    /**
     * Reserves a logical identity for this contract instance
     *
     * @param string $name Test-specific identity
     * @return string Unique logical key
     */
    private function key(string $name): string
    {
        $key = $this->prefix . 'contract:' . $name;
        $this->keys[] = $key;
        return $key;
    }

    /**
     * Gets the keys owned by this contract's targeted cleanup
     *
     * @return list<string> Reserved logical identities
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * Verifies versioning, inherited mutations, origin and expiration
     *
     * @return int Number of completed contract assertions
     */
    public function run(): int
    {
        $this->checkVersions();
        $this->checkValues();
        $this->checkCounters();
        $this->checkExpiration();
        return $this->count;
    }

    /**
     * Requires atomic creation and invalidation after every ordinary mutation
     */
    private function checkVersions(): void
    {
        $key = $this->key('versions');
        $first = $this->driver->getVersioned($key);
        $second = $this->driver->getVersioned($key);
        $this->check(!$first->exists(), 'Versioned read represents absence');
        $this->check(
            !$this->driver->compareAndDelete($first),
            'Conditional deletion of absence returns false'
        );
        $this->check(
            $this->driver->compareAndSwap($first, 'same', 0),
            'Absent observation supports atomic creation'
        );
        $this->check(
            !$this->driver->compareAndSwap($second, 'loser', 0),
            'Competing creation cannot overwrite the winner'
        );
        $before = $this->driver->getVersioned($key);
        $this->driver->set($key, 'same');
        $after = $this->driver->getVersioned($key);
        $this->check(
            $before->getVersion() !== $after->getVersion(),
            'Identical ordinary writes still create a new revision'
        );
        $this->check(
            !$this->driver->compareAndSwap($before, 'stale', 0)
                && !$this->driver->compareAndDelete($before),
            'Ordinary writes invalidate conditional writes and deletes'
        );
        $this->driver->delete($key);
        $this->driver->set($key, 'same');
        $this->check(
            !$this->driver->compareAndSwap($after, 'stale', 0),
            'Deleting and recreating identical content invalidates old tokens'
        );
        $foreign = $this->other->getVersioned($key);
        $rejected = false;
        try {
            $this->driver->compareAndSwap($foreign, 'foreign', 0);
        } catch (\InvalidArgumentException) {
            $rejected = true;
        }
        $this->check($rejected, 'Snapshots belong to the issuing adapter');
        $forged = new VersionedValue($key, 'same', 'invented', 0.0);
        $rejected = false;
        try {
            $this->driver->compareAndDelete($forged);
        } catch (\InvalidArgumentException) {
            $rejected = true;
        }
        $this->check($rejected, 'Fabricated snapshots are rejected');
        $current = $this->driver->getVersioned($key);
        $this->check(
            $this->driver->compareAndDelete($current),
            'Conditional deletion accepts the current version'
        );
        $this->check(
            !$this->driver->contains($key)
                && !$this->driver->compareAndDelete($current),
            'Conditional deletion is immediately observable and single-use'
        );
        $missing = false;
        try {
            $this->driver->get($key);
        } catch (KeyNotFoundException) {
            $missing = true;
        }
        $this->check($missing, 'Ordinary reads retain missing-key behavior');
    }

    /**
     * Requires exact scalar, null, array and binary-value round trips
     */
    private function checkValues(): void
    {
        $key = $this->key('values');
        $values = [null, false, true, 0, -12, 1.5, "x\0\xffy", [null, false]];
        foreach ($values as $value) {
            $this->driver->set($key, $value);
            $observed = $this->driver->getVersioned($key);
            $this->check(
                $observed->exists() && $observed->getValue() === $value
                    && $this->driver->get($key) === $value,
                'Ordinary and versioned reads preserve exact value types'
            );
        }
        $before = $this->driver->get($key);
        $rejected = false;
        try {
            $this->driver->set($key, new \stdClass());
        } catch (\InvalidArgumentException) {
            $rejected = true;
        }
        $this->check(
            $rejected && $this->driver->get($key) === $before,
            'Unsupported object payloads fail before modifying storage'
        );
        $rejected = false;
        try {
            $this->driver->set($key, 'invalid', -1);
        } catch (\InvalidArgumentException) {
            $rejected = true;
        }
        $this->check($rejected, 'Negative expiration is rejected');
    }

    /**
     * Requires atomic signed counters, consistent absence and overflow errors
     */
    private function checkCounters(): void
    {
        $key = $this->key('counter');
        $missing = false;
        try {
            $this->driver->increment($key);
        } catch (KeyNotFoundException) {
            $missing = true;
        }
        $this->check($missing, 'Counter initialization must be explicit');
        $this->driver->set($key, 0);
        $old = $this->driver->getVersioned($key);
        $this->check($this->driver->increment($key, 3) === 3, 'Increment');
        $this->check(
            !$this->driver->compareAndSwap($old, 100, 0),
            'Increment invalidates prior versions'
        );
        $old = $this->driver->getVersioned($key);
        $this->check($this->driver->decrement($key, 5) === -2, 'Decrement');
        $this->check(
            !$this->driver->compareAndDelete($old),
            'Decrement invalidates prior versions'
        );
        $this->driver->set($key, PHP_INT_MAX);
        $overflow = false;
        try {
            $this->driver->increment($key);
        } catch (OperationFailedException) {
            $overflow = true;
        }
        $this->check(
            $overflow && $this->driver->get($key) === PHP_INT_MAX,
            'Overflow cannot corrupt a counter'
        );
        $this->driver->set($key, '12');
        $invalid = false;
        try {
            $this->driver->increment($key);
        } catch (OperationFailedException) {
            $invalid = true;
        }
        $this->check($invalid, 'Counter operations require a real integer');
    }

    /**
     * Requires preserved deadlines and rejection of expired observations
     */
    private function checkExpiration(): void
    {
        $key = $this->key('expiration');
        $this->driver->set($key, 1, 1);
        $before = $this->driver->getVersioned($key);
        $this->driver->increment($key);
        $current = $this->driver->getVersioned($key);
        $this->check(
            $current->getExpiresAt() === $before->getExpiresAt(),
            'Counter updates preserve the exact original deadline'
        );
        $this->check(
            !$this->driver->compareAndSwap($before, 100, 900),
            'Failed conditional writes cannot extend expiration'
        );
        usleep(1200000);
        $this->check(
            !$this->driver->contains($key)
                && !$this->driver->getVersioned($key)->exists(),
            'Expired values are observably absent'
        );
        $this->check(
            !$this->driver->compareAndSwap($current, 100, 0)
                && !$this->driver->compareAndDelete($current),
            'Expired snapshots cannot mutate storage'
        );
        $absent = $this->driver->getVersioned($key);
        $this->check(
            $this->driver->compareAndSwap($absent, 'renewed', 0),
            'Fresh absence permits creation after expiration'
        );
    }
}
