<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\UnitTest\Result;

/**
 * Maps the native runner's method-based tests to isolated vendor operations
 */
abstract class AtomicAdapterTest
{
    /** Child runtime retained for this adapter test class */
    private AtomicTestSession $session;

    /**
     * Captures the backend selected by a concrete adapter test
     */
    public function __construct()
    {
        $this->session = new AtomicTestSession($this->getBackend());
    }

    /**
     * Identifies the real adapter exercised by the concrete test class
     *
     * @return string redis, memcached or apcu
     */
    abstract protected function getBackend(): string;

    /**
     * Checks explicit absence and stored-null observations
     *
     * @return list<Result> Native driver assertions
     */
    public function getVersioned(): array
    {
        return $this->session->run('getVersioned');
    }

    /**
     * Checks competing conditional creation
     *
     * @return list<Result> Native driver assertions
     */
    public function compareAndSwap(): array
    {
        return $this->session->run('compareAndSwap');
    }

    /**
     * Checks conditional deletion and stale-owner rejection
     *
     * @return list<Result> Native driver assertions
     */
    public function compareAndDelete(): array
    {
        return $this->session->run('compareAndDelete');
    }

    /**
     * Checks binary values and version invalidation after identical writes
     *
     * @return list<Result> Native driver assertions
     */
    public function set(): array
    {
        return $this->session->run('set');
    }

    /**
     * Checks existence independently of stored false values
     *
     * @return list<Result> Native driver assertions
     */
    public function contains(): array
    {
        return $this->session->run('contains');
    }

    /**
     * Checks ordinary missing-key errors and typed values
     *
     * @return list<Result> Native driver assertions
     */
    public function get(): array
    {
        return $this->session->run('get');
    }

    /**
     * Checks integer increments, preserved deadlines and changed versions
     *
     * @return list<Result> Native driver assertions
     */
    public function increment(): array
    {
        return $this->session->run('increment');
    }

    /**
     * Checks signed decrements and integer-overflow rejection
     *
     * @return list<Result> Native driver assertions
     */
    public function decrement(): array
    {
        return $this->session->run('decrement');
    }

    /**
     * Checks ordinary deletion invalidates prior observations
     *
     * @return list<Result> Native driver assertions
     */
    public function delete(): array
    {
        return $this->session->run('delete');
    }

    /**
     * Tests flush only against the worker's private backend
     *
     * @return list<Result> Native driver assertions
     */
    public function flush(): array
    {
        return $this->session->run('flush');
    }

    /**
     * Runs the shared adapter contract in the same isolated test runtime
     *
     * @return list<Result> Shared contract assertions
     */
    public function contract(): array
    {
        return $this->session->run('contract');
    }
}
