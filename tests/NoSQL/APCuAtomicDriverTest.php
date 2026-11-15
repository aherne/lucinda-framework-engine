<?php

namespace Test\Lucinda\Framework\NoSQL;

use Test\Lucinda\Framework\Support\AtomicAdapterTest;

/**
 * Exercises the APCu adapter against a private real backend
 */
final class APCuAtomicDriverTest extends AtomicAdapterTest
{
    /**
     * Selects the backend owned by this test class
     *
     * @return string Native backend name
     */
    protected function getBackend(): string
    {
        return 'apcu';
    }
}
