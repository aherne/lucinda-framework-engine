<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Locks;

use Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock;
use Lucinda\Framework\Persistence\Queries\Locks\AcquireLock;
use Lucinda\Framework\Persistence\SqlVendor;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Persistence\Queries\QueryContract;

class ReleaseLockTest extends QueryContract
{
    protected function cases(): array
    {
        $name = "lock' OR 1=1 --";
        $postgres = new ReleaseLock(SqlVendor::PostgreSQL, $name);
        return [
            [
                new ReleaseLock(SqlVendor::MySQL, $name),
                [
                    ':name' => ['value' => $name, 'type' => DataType::STRING],
                ],
                true,
                'RELEASE_LOCK(',
            ],
            [
                $postgres,
                [
                    ':first' => ['value' => 1815953475, 'type' => DataType::INTEGER],
                    ':second' => ['value' => -1593345681, 'type' => DataType::INTEGER],
                ],
                true,
                'pg_advisory_unlock(',
            ],
        ];
    }

    public function ownershipKeys(): array
    {
        $name = "same lock";
        $acquire = new AcquireLock(SqlVendor::PostgreSQL, $name);
        $release = new ReleaseLock(SqlVendor::PostgreSQL, $name);
        $parameters = $acquire->getParameters();
        return [
            new Result($parameters === $release->getParameters(), 'acquire and release address the same PostgreSQL advisory key'),
            new Result(
                pack('NN', $parameters[':first']['value'], $parameters[':second']['value']) === substr(hash('sha256', $name, true), 0, 8),
                'lock name maps deterministically to 64 hash bits'
            ),
            new Result(
                $parameters !== (new AcquireLock(SqlVendor::PostgreSQL, 'other lock'))->getParameters(),
                'different lock names remain independent'
            ),
            new Result(!str_contains($acquire->getSql(), 'FROM') && !str_contains($release->getSql(), 'FROM'), 'scalar lock queries require no dummy table'),
        ];
    }

    public function sqliteUnsupported(): array
    {
        try {
            new ReleaseLock(SqlVendor::SQLite, 'name');
        } catch (\LogicException $error) {
            return [new Result(str_contains($error->getMessage(), 'unsupported'), 'SQLite advisory locks fail explicitly')];
        }
        return [new Result(false, 'SQLite must not pretend to release a lock')];
    }
}
