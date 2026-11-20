<?php

namespace Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock;

use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\SQL\DataType;

/** Supplies the MySQL advisory-lock bindings and function. @internal */
final class MySQL extends AbstractQuery
{
    /**
     * Records runtime values with their explicit SQL binding types.
     *
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @return void
     */
    protected function setParameters(string $name): void
    {
        $this->parameters = new Parameters();
        $this->parameters->set(':name', $name, DataType::STRING);
    }

    /**
     * Returns the backend expression executed for this operation.
     *
     * @return string
     */
    protected function getExpression(): string
    {
        return 'RELEASE_LOCK(:name)';
    }
}
