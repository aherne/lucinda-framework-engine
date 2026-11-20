<?php

namespace Test\Lucinda\Framework\Persistence\Queries\Throttles;

use Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt;
use Lucinda\Framework\Persistence\SqlVendor;
use Lucinda\SQL\DataType;
use Test\Lucinda\Framework\Persistence\Queries\QueryContract;

class PenalizeAttemptTest extends QueryContract
{
    protected function cases(): array
    {
        $cases = [];
        $key = "user' OR 1=1 --";
        foreach (SqlVendor::cases() as $vendor) {
            $cases[] = [
                new PenalizeAttempt($vendor, $key, 100, 30),
                [
                    ':key' => ['value' => $key, 'type' => DataType::STRING],
                    ':expires' => ['value' => 130, 'type' => DataType::INTEGER],
                    ':now' => ['value' => 100, 'type' => DataType::INTEGER],
                    ':reset_now' => ['value' => 100, 'type' => DataType::INTEGER],
                    ':reset_expires' => ['value' => 130, 'type' => DataType::INTEGER],
                ],
                false,
                $vendor === SqlVendor::MySQL ? 'ON DUPLICATE KEY UPDATE' : 'ON CONFLICT (bucket) DO UPDATE SET',
            ];
        }
        return $cases;
    }
}
