<?php

namespace Test\Lucinda\Framework\Persistence;

use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\Framework\Persistence\SqlExecutor;
use Lucinda\Framework\Persistence\SqlVendor;
use Lucinda\Query\Insert;
use Lucinda\Query\Select;
use Lucinda\SQL\Connection;
use Lucinda\SQL\DataSource;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;
use Test\Lucinda\Framework\Support\SqlFixture;
use Test\Lucinda\Framework\Support\SqlQuery;

final class SqlExecutorTest
{
    private function context(): array
    {
        $connection = SqlFixture::instance()->connection();
        $connection->statement()->execute('CREATE TEMPORARY TABLE executor_rows (id INT AUTO_INCREMENT PRIMARY KEY, value VARBINARY(1024), flag BOOLEAN)');
        return [$connection, new SqlExecutor($connection)];
    }

    private function insert(string $value): SqlQuery
    {
        $statement = new Insert('executor_rows');
        $statement->columns(['value', 'flag']);
        $statement->values([':value', ':flag']);
        $parameters = new Parameters();
        $parameters->set(':value', $value, DataType::STRING)->set(':flag', false, DataType::BOOLEAN);
        return new SqlQuery($statement, $parameters);
    }

    public function read(): array
    {
        [$connection, $executor] = $this->context();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "binary\0\xff");
        rewind($stream);
        try {
            $select = new Select('DUAL');
            $select->fields()->add(':id', 'id')->add(':flag', 'flag')->add(':empty', 'empty_value')->add(':bytes', 'bytes');
            $parameters = new Parameters();
            $parameters->set(':id', '42', DataType::INTEGER)
                ->set(':flag', false, DataType::BOOLEAN)
                ->set(':empty', null, DataType::NULLABLE)
                ->set(':bytes', $stream, DataType::STREAMED);
            $row = $executor->read(new SqlQuery($select, $parameters))->toRow();
            return [
                new Result($row->getInteger('id') === 42 && $row->getBoolean('flag') === false, 'executor uses explicit integer and boolean bindings'),
                new Result($row->getString('empty_value') === null && $row->getStreamed('bytes') === "binary\0\xff", 'executor preserves null and stream input'),
                Assertions::throws(fn() => $executor->read($this->insert('wrong category')), \LogicException::class, 'read rejects writes before execution'),
            ];
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
    }

    public function write(): array
    {
        [$connection, $executor] = $this->context();
        $write = $executor->write($this->insert("x\0\xff"));
        $id = $write->getInsertId();
        $select = new Select('executor_rows');
        $row = $executor->read(new SqlQuery($select))->toRow();
        return [
            new Result($write->getAffectedRows() === 1 && $id === 1, 'write exposes affected count and generated ID'),
            new Result($row->getStreamed('value') === "x\0\xff" && $row->getBoolean('flag') === false, 'bound write values survive round-trip'),
            Assertions::throws(fn() => $executor->write(new SqlQuery(new Select('DUAL'))), \LogicException::class, 'write rejects selections'),
        ];
    }

    public function begin(): array
    {
        [$connection, $executor] = $this->context();
        $executor->begin();
        try {
            return [
                new Result($connection->transaction()->isActive(), 'begin starts the retained connection transaction'),
                Assertions::throws(fn() => $executor->begin(), \LogicException::class, 'nested transactions are rejected'),
            ];
        } finally {
            $executor->rollback();
        }
    }

    public function commit(): array
    {
        [$connection, $executor] = $this->context();
        $executor->begin();
        $executor->write($this->insert('committed'));
        $executor->commit();
        return [
            new Result(!$connection->transaction()->isActive(), 'commit ends the owned transaction'),
            new Result($executor->read(new SqlQuery(new Select('executor_rows')))->toRow()->getString('value') === 'committed', 'committed row remains available'),
            Assertions::throws(fn() => $executor->commit(), \LogicException::class, 'commit requires an owned transaction'),
        ];
    }

    public function rollback(): array
    {
        [$connection, $executor] = $this->context();
        $executor->begin();
        $executor->write($this->insert('rolled back'));
        $executor->rollback();
        $executor->rollback();
        return [
            new Result(!$connection->transaction()->isActive(), 'rollback ends transaction and is harmless when repeated'),
            new Result($executor->read(new SqlQuery(new Select('executor_rows')))->toList() === [], 'rolled-back row is absent'),
        ];
    }

    public function requireAutocommit(): array
    {
        [$connection, $executor] = $this->context();
        $executor->requireAutocommit();
        $executor->begin();
        $results = [Assertions::throws(fn() => $executor->requireAutocommit(), \LogicException::class, 'active transactions are rejected')];
        $executor->rollback();
        $connection->statement()->execute('SET SESSION autocommit=0');
        try {
            $results[] = Assertions::throws(fn() => $executor->requireAutocommit(), \LogicException::class, 'disabled MySQL autocommit is rejected');
        } finally {
            $connection->statement()->execute('SET SESSION autocommit=1');
        }
        $executor->requireAutocommit();
        $results[] = new Result(true, 'restored autocommit is accepted');
        return $results;
    }

    public function getVendor(): array
    {
        $results = [];
        foreach (['mysql' => SqlVendor::MySQL, 'pgsql' => SqlVendor::PostgreSQL, 'sqlite' => SqlVendor::SQLite] as $driver => $expected) {
            $connection = new Connection();
            (new \ReflectionProperty(Connection::class, 'dataSource'))->setValue(
                $connection,
                new DataSource(new \SimpleXMLElement('<server driver="' . $driver . '" host="unused" username="unused" file=":memory:"/>'))
            );
            $results[] = new Result((new SqlExecutor($connection))->getVendor() === $expected, 'configured driver selects ' . $expected->name);
        }
        $connection = new Connection();
        (new \ReflectionProperty(Connection::class, 'dataSource'))->setValue($connection, new DataSource(new \SimpleXMLElement('<server driver="odbc" host="unused" username="unused"/>')));
        $results[] = Assertions::throws(fn() => (new SqlExecutor($connection))->getVendor(), \LogicException::class, 'unknown dialect is rejected');
        return $results;
    }
}
