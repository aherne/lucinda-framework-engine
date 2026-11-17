<?php

namespace Test\Lucinda\Framework\Persistence\Results;

use Lucinda\Framework\Persistence\Results\Scalar;
use Lucinda\UnitTest\Result;

final class ScalarTest
{
    public function getAffectedRows(): array
    {
        $results = [];
        foreach ([0, 1, 42, PHP_INT_MAX] as $affected) {
            $results[] = new Result(
                (new Scalar($affected, 99))->getAffectedRows() === $affected,
                'affected count is retained independently of the insert identifier'
            );
        }
        return $results;
    }

    public function getInsertId(): array
    {
        $results = [];
        foreach ([0, 1, 42, PHP_INT_MAX] as $id) {
            $results[] = new Result(
                (new Scalar(7, $id))->getInsertId() === $id,
                'insert identifier is retained independently of the affected count'
            );
        }
        return $results;
    }

}
