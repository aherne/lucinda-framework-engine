<?php

namespace Test\Lucinda\Framework\Persistence\Builders;

use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\UnitTest\Result;

final class RowTest
{
    public function getString(): array
    {
        return $this->check('getString', [
            ['', ''], ['0012', '0012'], ["binary\0\xff", "binary\0\xff"], ['România', 'România'],
        ], [null, 12, false, 1.5, [], new \stdClass()]);
    }

    public function getInteger(): array
    {
        $valid = [];
        foreach ([0, -1, 42, PHP_INT_MIN, PHP_INT_MAX] as $value) {
            $valid[] = [$value, $value];
            $valid[] = [(string) $value, $value];
        }
        return $this->check('getInteger', $valid, [
            null, true, false, 1.5, '', 'abc', '1.5', '1e2', (string) PHP_INT_MAX . '0', [], new \stdClass(),
        ]);
    }

    public function getBoolean(): array
    {
        return $this->check('getBoolean', [
            [0, false], ['0', false], [1, true], ['1', true],
        ], [null, true, false, -1, 2, '2', 'true', 1.0, []]);
    }

    public function getNullableString(): array
    {
        return $this->check('getNullableString', [
            [null, null], ['', ''], ['0012', '0012'],
        ], [0, false, 1.5, [], new \stdClass()]);
    }

    public function getNullableInteger(): array
    {
        $valid = [[null, null]];
        foreach ([0, -1, PHP_INT_MIN, PHP_INT_MAX] as $value) {
            $valid[] = [$value, $value];
            $valid[] = [(string) $value, $value];
        }
        return $this->check('getNullableInteger', $valid, [
            false, 1.5, '', 'abc', '1.5', (string) PHP_INT_MAX . '0', [],
        ]);
    }

    public function getScalar(): array
    {
        return $this->check('getScalar', [
            ['', ''], ['0012', '0012'], ["binary\0\xff", "binary\0\xff"],
            [0, '0'], [-1, '-1'], [PHP_INT_MAX, (string) PHP_INT_MAX],
        ], [null, true, false, 1.5, [], new \stdClass()]);
    }

    /** Exercises accepted values, rejected types, and omitted columns. */
    private function check(string $method, array $valid, array $invalid): array
    {
        $results = [];
        foreach ($valid as [$input, $expected]) {
            $results[] = new Result(
                (new Row(['value' => $input]))->$method('value') === $expected,
                $method . ' preserves the expected value and type for ' . get_debug_type($input)
            );
        }
        foreach ($invalid as $value) {
            $rejected = false;
            try {
                (new Row(['value' => $value]))->$method('value');
            } catch (\UnexpectedValueException) {
                $rejected = true;
            }
            $results[] = new Result($rejected, $method . ' rejects ' . get_debug_type($value));
        }
        $missing = false;
        try {
            (new Row([]))->$method('value');
        } catch (\UnexpectedValueException $error) {
            $missing = str_contains($error->getMessage(), 'value');
        }
        $results[] = new Result($missing, $method . ' rejects an omitted column and names it');
        return $results;
    }
}
