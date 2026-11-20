<?php

namespace Test\Lucinda\Framework\Persistence;

use Test\Lucinda\Framework\Support\AtomicTestSession;

/** Runs persistence invariants against an isolated APCu worker. */
final class NoSqlThrottleTest
{
    private AtomicTestSession $session;

    public function __construct()
    {
        $this->session = new AtomicTestSession('apcu');
    }

    public function penalize(): array
    {
        return $this->session->run('persistence:throttle.penalize');
    }

    public function blocked(): array
    {
        return $this->session->run('persistence:throttle.blocked');
    }

    public function validation(): array
    {
        return $this->session->run('persistence:throttle.validation');
    }
}
