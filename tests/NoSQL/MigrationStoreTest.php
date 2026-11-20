<?php

namespace Test\Lucinda\Framework\NoSQL;

use Test\Lucinda\Framework\Support\AtomicTestSession;

/** Runs persistence invariants against an isolated APCu worker. */
final class MigrationStoreTest
{
    private AtomicTestSession $session;

    public function __construct()
    {
        $this->session = new AtomicTestSession('apcu');
    }

    public function exists(): array
    {
        return $this->session->run('persistence:migration.exists');
    }

    public function create(): array
    {
        return $this->session->run('persistence:migration.create');
    }

    public function save(): array
    {
        return $this->session->run('persistence:migration.save');
    }

    public function read(): array
    {
        return $this->session->run('persistence:migration.read');
    }

    public function remove(): array
    {
        return $this->session->run('persistence:migration.remove');
    }

    public function validation(): array
    {
        return $this->session->run('persistence:migration.validation');
    }
}
