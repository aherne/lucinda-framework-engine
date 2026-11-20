<?php

namespace Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock;

use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\SQL\DataType;

/** Releases the lock on the same retained connection. @internal */
final class PostgreSQL extends AbstractQuery
{
    /**
     * Records runtime values with their explicit SQL binding types.
     *
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @return void
     */
    protected function setParameters(string $name): void
    {
        $parts = unpack('Nfirst/Nsecond', substr(hash('sha256', $name, true), 0, 8));
        $this->parameters = new Parameters();
        foreach ($parts as $part => $value) {
            $value = $value > 0x7fffffff ? $value - 0x100000000 : $value;
            $this->parameters->set(':' . $part, $value, DataType::INTEGER);
        }
    }

    /**
     * Returns the backend expression executed for this operation.
     *
     * @return string
     */
    protected function getExpression(): string
    {
        return 'CASE WHEN pg_advisory_unlock('.
            'CAST(:first AS integer), CAST(:second AS integer)'
            .') THEN 1 ELSE 0 END';
    }
}
