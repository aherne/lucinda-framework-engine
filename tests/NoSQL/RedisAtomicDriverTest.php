<?php

namespace Test\Lucinda\Framework\NoSQL;

use Test\Lucinda\Framework\Support\AtomicAdapterTest;
use Test\Lucinda\Framework\Support\AtomicTestSession;
use Lucinda\UnitTest\Result;

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

    /**
     * Runs the same contract through the Valkey driver's Redis protocol path
     *
     * @return list<Result> Valkey driver assertions on a compatible endpoint
     */
    public function valkey(): array
    {
        $session = new AtomicTestSession('valkey');
        return $session->run('contract');
    }
}
