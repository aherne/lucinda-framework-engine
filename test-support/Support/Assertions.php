<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\UnitTest\Result;

/** Assertions shared by tests whose failure paths must retain their exception types. */
final class Assertions
{
    /** Returns a failed assertion when no exception or a different exception is thrown. */
    public static function throws(callable $operation, string $exception, string $message): Result
    {
        try {
            $operation();
        } catch (\Throwable $error) {
            return new Result($error instanceof $exception, $message . ' (' . get_class($error) . ')');
        }
        return new Result(false, $message . ' (no exception)');
    }
}
