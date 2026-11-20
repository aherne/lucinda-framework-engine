<?php

namespace Test\Lucinda\Framework\Persistence\Queries;

use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\UnitTest\Result;

final class ValidationExceptionTest
{
    public function getExpectedValueType(): array
    {
        $plain = new ValidationException('integer', 'array');
        $keyed = new ValidationException('integer', 'array', 'count');
        $missing = new ValidationException('', '', 'count');
        return [
            new Result($plain->getExpectedValueType() === 'integer' && $plain->getMessage() === 'Expected integer, got array', 'plain validation metadata and message'),
            new Result($keyed->getExpectedValueType() === 'integer' && str_contains($keyed->getMessage(), 'count'), 'column context retains expected type'),
            new Result($missing->getExpectedValueType() === '' && $missing->getMessage() === 'Column count is missing', 'missing-column failure is distinct'),
        ];
    }

    public function getActualValueType(): array
    {
        return [
            new Result((new ValidationException('integer', 'array'))->getActualValueType() === 'array', 'actual type is retained'),
            new Result((new ValidationException('', '', 'count'))->getActualValueType() === '', 'missing column has no actual value type'),
        ];
    }
}
