<?php

namespace Test\Lucinda\Framework\NoSQL;

use Test\Lucinda\Framework\Support\AtomicAdapterTest;

/**
 * Exercises the Redis adapter against a private real backend
 */
final class RedisAtomicDriverTest extends AtomicAdapterTest
{
    /**
     * Selects the backend owned by this test class
     *
     * @return string Native backend name
     */
    protected function getBackend(): string
    {
        return 'redis';
    }
}
