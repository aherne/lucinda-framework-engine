<?php

namespace Test\Lucinda\Framework\Persistence;

use Test\Lucinda\Framework\Support\AtomicTestSession;

/** Runs persistence invariants against an isolated APCu worker. */
final class NoSqlExecutorTest
{
    private AtomicTestSession $session;

    public function __construct()
    {
        $this->session = new AtomicTestSession('apcu');
    }

    public function getKeyspace(): array
    {
        return $this->session->run('persistence:executor.getKeyspace');
    }

    public function read(): array
    {
        return $this->session->run('persistence:executor.read');
    }

    public function replace(): array
    {
        return $this->session->run('persistence:executor.replace');
    }

    public function remove(): array
    {
        return $this->session->run('persistence:executor.remove');
    }
}
