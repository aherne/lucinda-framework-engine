<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\NoSQL\OperationFailedException;
use Lucinda\NoSQL\Vendor\APCu\Driver;

/**
 * Publishes immutable APCu values through atomic integer revision changes
 *
 * Each record key points to a unique integer revision with immutable bytes.
 * CAS publishes the new revision, then obsolete bytes can be removed. Record
 * deadlines are logical; revision pointers survive until cache eviction.
 * Only workers sharing one APCu cache share this adapter's storage domain.
 */
final class APCuAtomicDriver extends AbstractAtomicDriver
{
    /**
     * Requires APCu to be enabled for the current PHP runtime
     *
     * @param Driver $driver Original APCu library driver
     * @param string $namespace Reserved atomic-record prefix
     * @throws \LogicException If APCu is missing or disabled
     */
    public function __construct(
        Driver $driver,
        string $namespace = 'lucinda:atomic:'
    ) {
        parent::__construct($driver, $namespace);
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            throw new \LogicException('APCu must be enabled for this runtime');
        }
    }

    /**
     * Captures bytes associated with the current integer revision
     *
     * @param string $key Namespaced record identity
     * @return StorageObservation Stable revision and its immutable bytes
     * @throws OperationFailedException If observation or validation fails
     */
    protected function readStorage(string $key): StorageObservation
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $revision = apcu_fetch($key, $found);
            if (!$found) {
                return new StorageObservation($key, null, null);
            }
            if (!is_int($revision) || $revision < 1) {
                throw new OperationFailedException(
                    'Invalid APCu revision'
                );
            }
            $value = apcu_fetch($key . ':' . $revision, $valueFound);
            $current = apcu_fetch($key, $stillFound);
            if (!$stillFound || $current !== $revision) {
                continue;
            }
            if (!$valueFound) {
                return new StorageObservation($key, null, $revision);
            }
            if (!is_string($value)) {
                throw new OperationFailedException(
                    'Invalid APCu record'
                );
            }
            return new StorageObservation($key, $value, $revision);
        }
        throw new OperationFailedException('APCu observation contention limit');
    }

    /**
     * Publishes immutable bytes only if the observed revision still matches
     *
     * APCu cannot CAS strings. Integer indirection avoids expiring mutexes
     * and the stale-owner writes that a get/set lock sequence would permit.
     *
     * @param StorageObservation $expected Current integer revision or absence
     * @param string $value Encoded replacement bytes
     * @param int $expiration Native value retention in seconds
     * @return bool True when accepted, false for a competing revision
     * @throws OperationFailedException If APCu cannot retain replacement bytes
     */
    protected function replaceStorage(
        StorageObservation $expected,
        string $value,
        int $expiration
    ): bool {
        $key = $expected->getKey();
        $revision = random_int(1, PHP_INT_MAX);
        $valueKey = $key . ':' . $revision;
        if (!apcu_add($valueKey, $value, $expiration)) {
            throw new OperationFailedException('APCu allocation failed');
        }
        $token = $expected->getToken();
        if ($token === null) {
            $accepted = apcu_add($key, $revision);
        } else {
            $accepted = apcu_cas($key, $token, $revision);
        }
        if (!$accepted) {
            apcu_delete($valueKey);
            return false;
        }
        if ($token !== null) {
            apcu_delete($key . ':' . $token);
        }
        return true;
    }
}
