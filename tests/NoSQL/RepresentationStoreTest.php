<?php

namespace Test\Lucinda\Framework\NoSQL;

use Test\Lucinda\Framework\Support\AtomicTestSession;

/** Runs persistence invariants against an isolated APCu worker. */
final class RepresentationStoreTest
{
    private AtomicTestSession $session;

    public function __construct()
    {
        $this->session = new AtomicTestSession('apcu');
    }

    public function getTime(): array
    {
        return $this->session->run('persistence:representation.getTime');
    }
}
