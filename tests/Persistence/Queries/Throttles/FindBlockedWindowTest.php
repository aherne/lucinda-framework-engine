<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Throttles;

use Lucinda\Framework\Persistence\Queries\Throttles\FindBlockedWindow;
use Lucinda\SQL\DataType;
use Test\Lucinda\Framework\Persistence\Queries\QueryContract;

final class FindBlockedWindowTest extends QueryContract
{
    protected function cases(): array
    {
        return [[
            new FindBlockedWindow("user' OR 1=1 --", 100, 5),
            [
                ':key' => ['value' => "user' OR 1=1 --", 'type' => DataType::STRING],
                ':now' => ['value' => 100, 'type' => DataType::INTEGER],
                ':limit' => ['value' => 5, 'type' => DataType::INTEGER],
            ],
            true,
            'FROM authentication_throttles',
        ]];
    }
}
