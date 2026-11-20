<?php

namespace Lucinda\Framework\Persistence\Queries\Throttles;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Framework\Persistence\SqlVendor;

/** Forwards the operation to the implementation for its connection's vendor. @internal */
final class PenalizeAttempt extends Query
{
    private Query $wrapper;

    /**
     * Captures the operation inputs and initializes its typed bindings.
     *
     * @param Lucinda\Framework\Persistence\SqlVendor $vendor Dialect of the connection that will execute this operation.
     * @param string $key Selected column or named parameter identifier.
     * @param int $now Current Unix timestamp used to evaluate expiration.
     * @param int $period Fixed failure-window lifetime in seconds.
     */
    public function __construct(SqlVendor $vendor, string $key, int $now, int $period)
    {
        if ($vendor === SqlVendor::MySQL) {
            $this->wrapper = new PenalizeAttempt\MySQL($key, $now, $period);
        } elseif ($vendor === SqlVendor::PostgreSQL) {
            $this->wrapper = new PenalizeAttempt\PostgreSQL($key, $now, $period);
        } else {
            $this->wrapper = new PenalizeAttempt\SQLite($key, $now, $period);
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
