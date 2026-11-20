<?php

namespace Test\Lucinda\Framework\Persistence\Locking;

use Test\Lucinda\Framework\Support\AtomicTestSession;

/** Runs persistence invariants against an isolated APCu worker. */
final class NoSqlLockTest
{
    private AtomicTestSession $session;

    public function __construct()
    {
        $this->session = new AtomicTestSession('apcu');
    }

    public function acquire(): array
    {
        return $this->session->run('persistence:lock.acquire');
    }

    public function read(): array
    {
        return $this->session->run('persistence:lock.read');
    }

    public function replace(): array
    {
        return $this->session->run('persistence:lock.replace');
    }

    public function renew(): array
    {
        return $this->session->run('persistence:lock.renew');
    }

    public function release(): array
    {
        return $this->session->run('persistence:lock.release');
    }

    public function validation(): array
    {
        return $this->session->run('persistence:lock.validation');
    }
}
