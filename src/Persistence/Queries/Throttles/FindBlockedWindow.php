<?php

namespace Lucinda\Framework\Persistence\Queries\Throttles;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\SQL\DataType;
use Lucinda\Query\Select;
use Lucinda\Query\Operator\Comparison as C;

/** Reads a live failure window using portable SQL. @internal */
final class FindBlockedWindow extends Query
{
    /**
     * Captures the operation inputs and initializes its typed bindings.
     *
     * @param string $key Selected column or named parameter identifier.
     * @param int $now Current Unix timestamp used to evaluate expiration.
     * @param int $limit Failure count at which a live window blocks attempts.
     */
    public function __construct(string $key, int $now, int $limit)
    {
        $this->parameters = new Parameters();
        $this->parameters
            ->set(':key', $key, DataType::STRING)
            ->set(':now', $now, DataType::INTEGER)
            ->set(':limit', $limit, DataType::INTEGER);
        $this->statement = new Select('authentication_throttles');
        $this->statement->fields()->add('1', 'value');
        $this->statement->where(['bucket' => ':key'])
            ->set('expires', ':now', C::GREATER)
            ->set('attempts', ':limit', C::GREATER_EQUALS);
    }
}
