<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock;

use Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock\PostgreSQL;
use Test\Lucinda\Framework\Persistence\Queries\Locks\ReleaseLockTest as OperationTest;

/** Applies the operation contract directly to the concrete vendor query. */
final class PostgreSQLTest extends OperationTest
{
    protected function cases(): array
    {
        $case = parent::cases()[1];
        $case[0] = new PostgreSQL("lock' OR 1=1 --");
        return [$case];
    }
}
