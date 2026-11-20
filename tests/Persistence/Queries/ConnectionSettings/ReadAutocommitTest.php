<?php

namespace Test\Lucinda\Framework\Persistence\Queries\ConnectionSettings;

use Lucinda\Framework\Persistence\Queries\ConnectionSettings\ReadAutocommit;
use Test\Lucinda\Framework\Persistence\Queries\QueryContract;

final class ReadAutocommitTest extends QueryContract
{
    protected function cases(): array
    {
        return [[new ReadAutocommit(), [], true, '@@autocommit']];
    }
}
