<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Throttles;

use Lucinda\Framework\Persistence\Queries\Throttles\DeleteExpiredWindows;
use Lucinda\SQL\DataType;
use Test\Lucinda\Framework\Persistence\Queries\QueryContract;

final class DeleteExpiredWindowsTest extends QueryContract
{
    protected function cases(): array
    {
        return [[
            new DeleteExpiredWindows(100),
            [':now' => ['value' => 100, 'type' => DataType::INTEGER]],
            false,
            'expires <= :now',
        ]];
    }
}
