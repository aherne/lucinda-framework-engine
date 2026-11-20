<?php

namespace Test\Lucinda\Framework\Persistence;

use Lucinda\Framework\Persistence\SqlExecutor;
use Lucinda\Framework\Persistence\SqlThrottle;
use Lucinda\Framework\Persistence\ThrottleBucket;
use Lucinda\Query\Select;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;
use Test\Lucinda\Framework\Support\SqlFixture;
use Test\Lucinda\Framework\Support\SqlQuery;

final class SqlThrottleTest
{
    private function context(): array
    {
        $connection = SqlFixture::instance()->connection();
        $connection->statement()->execute('CREATE TEMPORARY TABLE authentication_throttles (bucket VARCHAR(64) PRIMARY KEY, attempts INT NOT NULL, expires BIGINT NOT NULL)');
        $executor = new SqlExecutor($connection);
        return [$connection, $executor, new SqlThrottle($executor, 'form', 2, 30)];
    }

    public function penalize(): array
    {
        [$connection, $executor, $throttle] = $this->context();
        $throttle->penalize('alice', '192.0.2.1');
        $row = $executor->read(new SqlQuery(new Select('authentication_throttles')))->toRow();
        $results = [new Result($row->getInteger('attempts') === 1 && $row->getInteger('expires') > time(), 'first failure initializes the window')];
        $connection->statement()->execute('UPDATE authentication_throttles SET expires=UNIX_TIMESTAMP()+10');
        $before = $executor->read(new SqlQuery(new Select('authentication_throttles')))->toRow()->getInteger('expires');
        $throttle->penalize('alice', '192.0.2.1');
        $row = $executor->read(new SqlQuery(new Select('authentication_throttles')))->toRow();
        $results[] = new Result($row->getInteger('attempts') === 2 && $row->getInteger('expires') === $before, 'live window increments without extension');
        $connection->statement()->execute('UPDATE authentication_throttles SET attempts=1000000');
        $throttle->penalize('alice', '192.0.2.1');
        $results[] = new Result($executor->read(new SqlQuery(new Select('authentication_throttles')))->toRow()->getInteger('attempts') === 1000000, 'counter saturates');
        $connection->statement()->execute('UPDATE authentication_throttles SET expires=0, attempts=7');
        $throttle->penalize('alice', '192.0.2.1');
        $row = $executor->read(new SqlQuery(new Select('authentication_throttles')))->toRow();
        $results[] = new Result($row->getInteger('attempts') === 1 && $row->getInteger('expires') > time(), 'expired failure window resets');
        return $results;
    }

    public function blocked(): array
    {
        [$connection, $executor, $throttle] = $this->context();
        $results = [new Result(!$throttle->blocked('alice', '192.0.2.1'), 'no failure window means allowed')];
        $throttle->penalize('alice', '192.0.2.1');
        $results[] = new Result(!$throttle->blocked('alice', '192.0.2.1'), 'below threshold allowed');
        $throttle->penalize('alice', '192.0.2.1');
        $results[] = new Result($throttle->blocked('alice', '192.0.2.1'), 'threshold reached');
        $results[] = new Result(!$throttle->blocked('bob', '192.0.2.1') && !$throttle->blocked('alice', '192.0.2.2'), 'subject and address isolate windows');
        $results[] = new Result(!(new SqlThrottle($executor, 'mfa', 2, 30))->blocked('alice', '192.0.2.1'), 'scope isolates windows');
        $connection->statement()->execute('UPDATE authentication_throttles SET expires=UNIX_TIMESTAMP()');
        $results[] = new Result(!$throttle->blocked('alice', '192.0.2.1'), 'expired window no longer blocks');
        return $results;
    }

    public function clearExpired(): array
    {
        [$connection, $executor, $throttle] = $this->context();
        $throttle->penalize('alice', '192.0.2.1');
        $throttle->penalize('bob', '192.0.2.1');
        $update = $connection->preparedStatement();
        $update->prepare('UPDATE authentication_throttles SET expires=0 WHERE bucket=:key');
        $update->bind(':key', (new ThrottleBucket('form', 'alice', '192.0.2.1'))->getKey(), DataType::STRING);
        $update->execute();
        $throttle->clearExpired();
        $rows = $executor->read(new SqlQuery(new Select('authentication_throttles')))->toList();
        return [
            new Result(count($rows) === 1 && $rows[0]->getString('bucket') === (new ThrottleBucket('form', 'bob', '192.0.2.1'))->getKey(), 'cleanup deletes expired windows and retains live windows'),
            Assertions::throws(fn() => new SqlThrottle($executor, 'form', 0, 30), \InvalidArgumentException::class, 'invalid threshold rejected'),
            Assertions::throws(fn() => new SqlThrottle($executor, 'form', 2, 0), \InvalidArgumentException::class, 'invalid lifetime rejected'),
        ];
    }
}
