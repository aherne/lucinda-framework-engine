<?php

namespace Lucinda\Framework\NoSQL\AtomicDriver;

use Lucinda\NoSQL\OperationFailedException;
use Lucinda\NoSQL\Vendor\Memcached\Driver;
use Lucinda\Framework\NoSQL\StorageObservation;

/**
 * Adapts a Memcached connection to atomic add and compare-and-swap writes
 *
 * Native CAS protects complete records. The common adapter owns logical
 * revisions and the inherited driver's value and counter operations.
 */
final class MemcachedAtomicDriver extends AbstractAtomicDriver
{
    /** Native client retained for this adapter's lifetime */
    private \Memcached $client;

    /**
     * Captures the library driver and reserves the atomic record namespace
     *
     * @param Driver $driver Connected original library driver
     * @param string $namespace Reserved prefix for all adapter-managed keys
     */
    public function __construct(
        Driver $driver,
        string $namespace = 'lucinda:atomic:'
    ) {
        parent::__construct($driver, $namespace);
        $this->client = $driver->getDriver();
    }

    /**
     * Captures a value and its native CAS token in one read
     *
     * @param string $key Namespaced record identity
     * @return StorageObservation Current record and opaque native CAS token
     * @throws OperationFailedException If reading or validation fails
     */
    protected function readStorage(string $key): StorageObservation
    {
        $result = $this->client->get($key, null, \Memcached::GET_EXTENDED);
        if ($this->client->getResultCode() === \Memcached::RES_NOTFOUND) {
            return new StorageObservation($key, null, null);
        }
        if (
            $this->client->getResultCode() !== \Memcached::RES_SUCCESS
            || !is_array($result) || !is_string($result['value'] ?? null)
        ) {
            throw new OperationFailedException('Memcached record read failed');
        }
        return new StorageObservation($key, $result['value'], $result['cas']);
    }

    /**
     * Adds an absent record or replaces the exact version previously observed
     *
     * @param StorageObservation $expected Expected current record
     * @param string $value Encoded replacement bytes
     * @param int $expiration Retention in seconds, zero for no expiration
     * @return bool True when accepted, false for concurrent modification
     * @throws OperationFailedException If storage fails rather than racing
     */
    protected function replaceStorage(
        StorageObservation $expected,
        string $value,
        int $expiration
    ): bool {
        if ($expiration > 2592000) {
            $expiration = time() + $expiration;
        }
        $token = $expected->getToken();
        if ($token === null) {
            $accepted = $this->client->add(
                $expected->getKey(),
                $value,
                $expiration
            );
        } else {
            $accepted = $this->client->cas(
                $token,
                $expected->getKey(),
                $value,
                $expiration
            );
        }
        if ($accepted) {
            return true;
        }
        $code = $this->client->getResultCode();
        if (
            in_array($code, [
            \Memcached::RES_DATA_EXISTS,
            \Memcached::RES_NOTFOUND,
            \Memcached::RES_NOTSTORED,
            ], true)
        ) {
            return false;
        }
        throw new OperationFailedException($this->client->getResultMessage());
    }
}
