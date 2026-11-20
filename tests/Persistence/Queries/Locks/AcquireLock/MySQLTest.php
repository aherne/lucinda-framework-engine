<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Locks\AcquireLock;

use Lucinda\Framework\Persistence\Queries\Locks\AcquireLock\MySQL;
use Test\Lucinda\Framework\Persistence\Queries\Locks\AcquireLockTest as OperationTest;

/** Applies the operation contract directly to the concrete vendor query. */
final class MySQLTest extends OperationTest
{
    protected function cases(): array
    {
        $case = parent::cases()[0];
        $case[0] = new MySQL("lock' OR 1=1 --", 3);
        return [$case];
    }
}
