<?php

namespace Test\Lucinda\Framework\NoSQL\AtomicDriver;

use Test\Lucinda\Framework\Support\AtomicAdapterTest;

/**
 * Exercises the Memcached adapter against a private real backend
 */
final class MemcachedAtomicDriverTest extends AtomicAdapterTest
{
    /**
     * Selects the backend owned by this test class
     *
     * @return string Native backend name
     */
    protected function getBackend(): string
    {
        return 'memcached';
    }
}
