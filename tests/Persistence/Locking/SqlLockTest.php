<?php

namespace Test\Lucinda\Framework\Persistence\Locking;

use Lucinda\Framework\Persistence\Locking\SqlLock;
use Lucinda\Framework\Persistence\SqlExecutor;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;
use Test\Lucinda\Framework\Support\SqlFixture;

final class SqlLockTest
{
    public function acquire(): array
    {
        $connection = SqlFixture::instance()->connection();
        $first = new SqlExecutor($connection);
        $second = new SqlExecutor(SqlFixture::instance()->connection());
        $prefix = 'sql-lock-test:' . bin2hex(random_bytes(12));
        $owner = new SqlLock($first, $prefix, 0);
        $other = new SqlLock($second, $prefix, 0);
        try {
            $owner->acquire('one');
            $owner->acquire('one');
            $results = [Assertions::throws(fn() => $other->acquire('one'), \RuntimeException::class, 'another connection cannot acquire a live advisory lock')];
            $owner->release();
            $other->acquire('one');
            $other->release();
            $results[] = new Result(true, 'idempotent acquisition requires one release');
            $owner->acquire('one');
            $owner->acquire('two');
            $other->acquire('one');
            $other->release();
            $results[] = new Result(true, 'switching identities releases the previous lock');
            $owner->release();
            $left = new SqlLock($first, $prefix . 'a', 0);
            $right = new SqlLock($second, $prefix . 'ab', 0);
            $left->acquire('bc');
            $right->acquire('c');
            $left->release();
            $right->release();
            $results[] = new Result(true, 'namespace and identity boundaries do not collide');
            $results[] = Assertions::throws(fn() => new SqlLock($first, '', 0), \InvalidArgumentException::class, 'empty namespace rejected');
            $results[] = Assertions::throws(fn() => new SqlLock($first, $prefix, -1), \InvalidArgumentException::class, 'negative timeout rejected');
            $first->begin();
            $results[] = Assertions::throws(fn() => $owner->acquire('transaction'), \LogicException::class, 'advisory acquisition requires the autocommit policy');
            $first->rollback();
            return $results;
        } finally {
            $first->rollback();
            $owner->release();
            $other->release();
        }
    }

    public function release(): array
    {
        $connection = SqlFixture::instance()->connection();
        $executor = new SqlExecutor($connection);
        $owner = new SqlLock($executor, 'release-test:' . bin2hex(random_bytes(12)), 0);
        $owner->release();
        $owner->acquire('one');
        $owner->release();
        $owner->release();
        $results = [new Result(true, 'release before acquisition and repeated release are harmless')];
        $owner->acquire('lost');
        $connection->statement()->execute('SELECT RELEASE_ALL_LOCKS()');
        $results[] = Assertions::throws(fn() => $owner->release(), \RuntimeException::class, 'lost native ownership is reported');
        return $results;
    }

    public function timeout(): array
    {
        $prefix = 'wait-test:' . bin2hex(random_bytes(12));
        $owner = new SqlLock(new SqlExecutor(SqlFixture::instance()->connection()), $prefix, 0);
        $waiter = new SqlLock(new SqlExecutor(SqlFixture::instance()->connection()), $prefix, 1);
        try {
            $owner->acquire('one');
            $start = hrtime(true);
            $result = Assertions::throws(fn() => $waiter->acquire('one'), \RuntimeException::class, 'contended acquisition times out');
            $elapsed = (hrtime(true) - $start) / 1000000000;
            return [$result, new Result($elapsed >= 0.8 && $elapsed < 3.0, 'wait respects the configured monotonic deadline')];
        } finally {
            $owner->release();
            $waiter->release();
        }
    }
}
