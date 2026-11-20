<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Query\Delete;
use Lucinda\Query\Insert;
use Lucinda\Query\Select;
use Lucinda\Query\Update;

/** Supplies generated test statements through the same contract as application queries. */
final class SqlQuery extends Query
{
    public function __construct(Select|Insert|Update|Delete $statement, ?Parameters $parameters = null)
    {
        $this->statement = $statement;
        $this->parameters = $parameters;
    }
}
