<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt;

use Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt\MySQL;
use Test\Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttemptTest as OperationTest;

/** Applies the operation contract directly to the concrete vendor query. */
final class MySQLTest extends OperationTest
{
    protected function cases(): array
    {
        $case = parent::cases()[0];
        $case[0] = new MySQL("user' OR 1=1 --", 100, 30);
        return [$case];
    }
}
