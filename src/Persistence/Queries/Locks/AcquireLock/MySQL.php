<?php

namespace Lucinda\Framework\Persistence\Queries\Locks\AcquireLock;

use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\SQL\DataType;

/** Supplies the MySQL advisory-lock bindings and function. @internal */
final class MySQL extends AbstractQuery
{
    /**
     * Records runtime values with their explicit SQL binding types.
     *
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @param int $timeout Maximum MySQL wait in seconds; PostgreSQL retries are coordinated by SqlLock.
     * @return void
     */
    protected function setParameters(string $name, int $timeout): void
    {
        $this->parameters = new Parameters();
        $this->parameters
            ->set(':name', $name, DataType::STRING)
            ->set(":timeout", $timeout, DataType::INTEGER);
    }

    /**
     * Returns the backend expression executed for this operation.
     *
     * @return string
     */
    protected function getExpression(): string
    {
        return 'GET_LOCK(:name, :timeout)';
    }
}
