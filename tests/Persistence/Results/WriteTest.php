<?php

namespace Test\Lucinda\Framework\Persistence\Results;

use Lucinda\Framework\Persistence\Results\Write;
use Lucinda\SQL\StatementResults;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\SqlFixture;

final class WriteTest
{
    public function getAffectedRows(): array
    {
        $pdo = SqlFixture::instance()->pdo();
        $pdo->exec('CREATE TEMPORARY TABLE affected_rows (id INT PRIMARY KEY, value INT)');
        $pdo->exec('INSERT INTO affected_rows VALUES (1, 10), (2, 20)');
        $changed = new Write(new StatementResults($pdo, $pdo->query('UPDATE affected_rows SET value=value+1')));
        $missing = new Write(new StatementResults($pdo, $pdo->query('DELETE FROM affected_rows WHERE id=99')));
        return [
            new Result($changed->getAffectedRows() === 2, 'write reports the changed row count'),
            new Result($missing->getAffectedRows() === 0, 'unmatched write reports zero'),
        ];
    }

    public function getInsertId(): array
    {
        $pdo = SqlFixture::instance()->pdo();
        $pdo->exec('CREATE TEMPORARY TABLE inserted_rows (id INT AUTO_INCREMENT PRIMARY KEY, value INT)');
        $first = new Write(new StatementResults($pdo, $pdo->query('INSERT INTO inserted_rows(value) VALUES (10)')));
        $firstId = $first->getInsertId();
        $second = new Write(new StatementResults($pdo, $pdo->query('INSERT INTO inserted_rows(value) VALUES (20)')));
        return [
            new Result($firstId === 1, 'first generated identifier is read immediately'),
            new Result($second->getInsertId() === 2, 'subsequent insert reports its own identifier'),
        ];
    }
}
