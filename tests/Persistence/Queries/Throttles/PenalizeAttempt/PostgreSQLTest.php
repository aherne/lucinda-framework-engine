<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt;

use Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt\PostgreSQL;
use Test\Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttemptTest as OperationTest;

/** Applies the operation contract directly to the concrete vendor query. */
final class PostgreSQLTest extends OperationTest
{
    protected function cases(): array
    {
        $case = parent::cases()[1];
        $case[0] = new PostgreSQL("user' OR 1=1 --", 100, 30);
        return [$case];
    }
}
