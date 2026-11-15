<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\NoSQL\OperationFailedException;
use Lucinda\NoSQL\Vendor\Redis\Driver;

/**
 * Adapts Redis and compatible Valkey drivers to versioned atomic operations
 *
 * Native comparison uses Lua while the common adapter owns logical versions.
 * No framework session, migration or throttling policy is encoded here.
 */
final class RedisAtomicDriver extends AbstractAtomicDriver
{
    /** Native client retained for this adapter's lifetime */
    private \Redis $client;

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
     * Captures the current record bytes as their own comparison token
     *
     * @param string $key Namespaced record identity
     * @return StorageObservation Current bytes or an absent observation
     * @throws OperationFailedException If Redis cannot read the key
     */
    protected function readStorage(string $key): StorageObservation
    {
        try {
            $this->client->clearLastError();
            $value = $this->client->get($key);
        } catch (\RedisException $error) {
            throw new OperationFailedException($error->getMessage(), 0, $error);
        }
        if ($value === false) {
            $error = $this->client->getLastError();
            if ($error) {
                throw new OperationFailedException($error);
            }
            $value = null;
        }
        return new StorageObservation($key, $value, $value);
    }

    /**
     * Atomically compares record bytes and publishes their replacement
     *
     * @param StorageObservation $expected Expected record bytes or absence
     * @param string $value Encoded replacement record
     * @param int $expiration Retention in seconds, zero for no expiration
     * @return bool True when accepted, false for a stale observation
     * @throws OperationFailedException If script execution fails
     */
    protected function replaceStorage(
        StorageObservation $expected,
        string $value,
        int $expiration
    ): bool {
        $source = <<<'LUA'
local current = redis.call('GET', KEYS[1])
if ARGV[1] == 'missing' then
    if current then return 0 end
elseif current ~= ARGV[2] then
    return 0
end
redis.call('SET', KEYS[1], ARGV[3])
if tonumber(ARGV[4]) > 0 then
    redis.call('EXPIRE', KEYS[1], ARGV[4])
end
return 1
LUA;
        $token = $expected->getToken();
        try {
            $result = $this->client->eval($source, [
                $expected->getKey(),
                $token === null ? 'missing' : 'present',
                $token ?? '',
                $value,
                $expiration,
            ], 1);
        } catch (\RedisException $error) {
            throw new OperationFailedException($error->getMessage(), 0, $error);
        }
        if ($result === false) {
            throw new OperationFailedException('Redis replacement failed');
        }
        return $result === 1;
    }
}
