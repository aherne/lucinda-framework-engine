<?php

namespace Lucinda\Framework\Persistence\Queries\Throttles;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\SQL\DataType;
use Lucinda\Query\Delete;
use Lucinda\Query\Operator\Comparison as C;

/** Deletes expired failure windows using portable SQL. @internal */
final class DeleteExpiredWindows extends Query
{
    /**
     * Captures the operation inputs and initializes its typed bindings.
     *
     * @param int $now Current Unix timestamp used to evaluate expiration.
     */
    public function __construct(int $now)
    {
        $this->parameters = new Parameters();
        $this->parameters->set(':now', $now, DataType::INTEGER);
        $this->statement = new Delete('authentication_throttles');
        $this->statement->where()->set('expires', ':now', C::LESSER_EQUALS);
    }
}
