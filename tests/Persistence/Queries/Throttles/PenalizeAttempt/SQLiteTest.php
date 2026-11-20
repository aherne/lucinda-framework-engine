<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt;

use Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt\SQLite;
use Test\Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttemptTest as OperationTest;

/** Applies the operation contract directly to the concrete vendor query. */
final class SQLiteTest extends OperationTest
{
    protected function cases(): array
    {
        $case = parent::cases()[2];
        $case[0] = new SQLite("user' OR 1=1 --", 100, 30);
        return [$case];
    }
}
