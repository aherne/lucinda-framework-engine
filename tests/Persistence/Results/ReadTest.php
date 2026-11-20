<?php

namespace Test\Lucinda\Framework\Persistence\Results;

use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\Framework\Persistence\Results\Read;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;
use Test\Lucinda\Framework\Support\SqlFixture;

final class ReadTest
{
    private function result(string $sql): Read
    {
        return new Read(SqlFixture::results($sql));
    }

    public function toValue(): array
    {
        return [
            new Result($this->result('SELECT 1')->toValue(DataType::INTEGER) === 1, 'typed integer scalar'),
            new Result($this->result('SELECT 0')->toValue(DataType::BOOLEAN) === false, 'false is distinct from no value'),
            new Result($this->result('SELECT NULL')->toValue(DataType::INTEGER) === null, 'SQL NULL bypasses non-null validation'),
            new Result($this->result('SELECT 1 WHERE 1=0')->toValue(DataType::INTEGER) === null, 'missing row returns null'),
            new Result($this->result("SELECT ''")->toValue(DataType::STRING) === '', 'empty string remains a value'),
            Assertions::throws(fn() => $this->result("SELECT 'bad'")->toValue(DataType::INTEGER), ValidationException::class, 'invalid scalar is rejected'),
        ];
    }

    public function toColumn(): array
    {
        return [
            new Result(
                $this->result("SELECT '1' UNION ALL SELECT NULL UNION ALL SELECT '51' UNION ALL SELECT '23' UNION ALL SELECT NULL")->toColumn(DataType::INTEGER)
                    === [1, null, 51, 23, null],
                'column values are normalized without removing nulls or changing order'
            ),
            new Result($this->result('SELECT 1 WHERE 1=0')->toColumn(DataType::INTEGER) === [], 'empty column is an empty list'),
            Assertions::throws(fn() => $this->result("SELECT '1' UNION ALL SELECT 'bad'")->toColumn(DataType::INTEGER), ValidationException::class, 'invalid later element is rejected'),
        ];
    }

    public function toMap(): array
    {
        return [
            new Result($this->result('SELECT 1 AS id, NULL AS value UNION ALL SELECT 2, 51')->toMap('id', 'value', DataType::INTEGER, DataType::INTEGER) === [1 => null, 2 => 51], 'map preserves nullable typed values'),
            new Result($this->result("SELECT 'a' AS id, 0 AS value UNION ALL SELECT 'b', 1")->toMap('id', 'value', DataType::STRING, DataType::BOOLEAN) === ['a' => false, 'b' => true], 'key and value use independent types'),
            new Result($this->result("SELECT 1 AS id, 'first' AS value UNION ALL SELECT 1, 'last'")->toMap('id', 'value', DataType::INTEGER, DataType::STRING) === [1 => 'last'], 'duplicate map keys deliberately retain the later value'),
            new Result($this->result('SELECT 1 AS id, 2 AS value WHERE 1=0')->toMap('id', 'value', DataType::INTEGER, DataType::INTEGER) === [], 'empty map'),
            Assertions::throws(fn() => $this->result('SELECT 1 AS id')->toMap('missing', 'id', DataType::INTEGER, DataType::INTEGER), \UnexpectedValueException::class, 'missing key column is rejected'),
            Assertions::throws(fn() => $this->result('SELECT 1 AS id')->toMap('id', 'missing', DataType::INTEGER, DataType::INTEGER), \UnexpectedValueException::class, 'missing value column is rejected'),
            Assertions::throws(fn() => $this->result('SELECT NULL AS id, 1 AS value')->toMap('id', 'value', DataType::INTEGER, DataType::INTEGER), ValidationException::class, 'null keys are rejected'),
            Assertions::throws(fn() => $this->result('SELECT 1 AS id, 2 AS value')->toMap('id', 'value', DataType::BOOLEAN, DataType::INTEGER), \UnexpectedValueException::class, 'non-key datatypes are rejected'),
            Assertions::throws(fn() => $this->result("SELECT 1 AS id, 'bad' AS value")->toMap('id', 'value', DataType::INTEGER, DataType::INTEGER), ValidationException::class, 'invalid mapped value is rejected'),
        ];
    }

    public function toRow(): array
    {
        $result = $this->result("SELECT 1 AS id, X'00ff' AS payload UNION ALL SELECT 2, NULL");
        $first = $result->toRow();
        $second = $result->toRow();
        return [
            new Result($first instanceof Row && $first->getInteger('id') === 1 && $first->getStreamed('payload') === "\0\xff", 'row wraps typed and binary values'),
            new Result($second instanceof Row && $second->getInteger('id') === 2 && $second->getString('payload') === null, 'cursor advances and preserves null'),
            new Result($result->toRow() === null, 'end of result returns null'),
        ];
    }

    public function toList(): array
    {
        $rows = $this->result("SELECT NULL AS id, 'first' AS label UNION ALL SELECT NULL, 'second' UNION ALL SELECT 1, 'third' UNION ALL SELECT 1, 'fourth'")->toList();
        return [
            new Result(count($rows) === 4 && array_keys($rows) === [0, 1, 2, 3], 'null and duplicate identities never remove selected rows'),
            new Result(array_map(fn(Row $row) => $row->getString('label'), $rows) === ['first', 'second', 'third', 'fourth'], 'result-set order is retained'),
            new Result($this->result('SELECT 1 WHERE 1=0')->toList() === [], 'empty selection returns an empty list'),
        ];
    }
}
