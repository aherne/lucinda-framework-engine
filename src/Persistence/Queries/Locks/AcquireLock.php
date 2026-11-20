<?php

namespace Lucinda\Framework\Persistence\Queries\Locks;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Framework\Persistence\SqlVendor;

/** Forwards the operation to the implementation for its connection's vendor. @internal */
final class AcquireLock extends Query
{
    private Query $wrapper;

    /**
     * Captures the operation inputs and initializes its typed bindings.
     *
     * @param Lucinda\Framework\Persistence\SqlVendor $vendor Dialect of the connection that will execute this operation.
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @param int $timeout Maximum MySQL wait in seconds; PostgreSQL retries are coordinated by SqlLock.
     */
    public function __construct(SqlVendor $vendor, string $name, int $timeout = 10)
    {
        if ($vendor === SqlVendor::MySQL) {
            $this->wrapper = new AcquireLock\MySQL($name, $timeout);
        } elseif ($vendor === SqlVendor::PostgreSQL) {
            $this->wrapper = new AcquireLock\PostgreSQL($name, $timeout);
        } else {
            throw new \LogicException('SQLite named advisory locks are unsupported');
        }
    }

    /**
     * Renders the operation as SQL with named placeholders.
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->wrapper->getSql();
    }

    /**
     * Returns the named values and SQL types of the wrapped operation.
     *
     * @return array<string, array{value: mixed, type: \Lucinda\SQL\DataType}> Named bindings.
     */
    public function getParameters(): array
    {
        return $this->wrapper->getParameters();
    }

    /**
     * Reports whether this operation returns selected rows.
     *
     * @return bool
     */
    public function isSelection(): bool
    {
        return $this->wrapper->isSelection();
    }
}
