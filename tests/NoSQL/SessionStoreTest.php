<?php

namespace Test\Lucinda\Framework\NoSQL;

use Test\Lucinda\Framework\Support\AtomicTestSession;

/** Runs persistence invariants against an isolated APCu worker. */
final class SessionStoreTest
{
    private AtomicTestSession $session;

    public function __construct()
    {
        $this->session = new AtomicTestSession('apcu');
    }

    public function read(): array
    {
        return $this->session->run('persistence:session.read');
    }

    public function write(): array
    {
        return $this->session->run('persistence:session.write');
    }

    public function destroy(): array
    {
        return $this->session->run('persistence:session.destroy');
    }

    public function renew(): array
    {
        return $this->session->run('persistence:session.renew');
    }

    public function close(): array
    {
        return $this->session->run('persistence:session.close');
    }

    public function validation(): array
    {
        return $this->session->run('persistence:session.validation');
    }
}
