<?php

namespace Test\Lucinda\Framework\Persistence\Builders;

use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;

/** Exercises nullable projections, normalization and column-specific failures. */
final class RowTest
{
    private ParametersValidator $validator;

    public function __construct()
    {
        $this->validator = new ParametersValidator();
    }

    public function getString(): array
    {
        return $this->check('getString', [
            [null, null], ['', ''], ['0012', '0012'], ["binary\0\xff", "binary\0\xff"],
            [12, '12'], [1.5, '1.5'],
        ], [false, true, [], new \stdClass()]);
    }

    public function getInteger(): array
    {
        $valid = [[null, null]];
        foreach ([0, -1, 42, PHP_INT_MIN, PHP_INT_MAX] as $value) {
            $valid[] = [$value, $value];
            $valid[] = [(string) $value, $value];
        }
        return $this->check('getInteger', $valid, [
            true, false, 1.0, '', '1.5', '1e2', 'not an integer', (string) PHP_INT_MAX . '0', [], new \stdClass(),
        ]);
    }

    public function getBoolean(): array
    {
        return $this->check('getBoolean', [
            [null, null], [true, true], [false, false], [0, false], ['0', false],
            [1, true], ['1', true], ['true', true], ['false', false],
        ], [-1, 2, '2', 'TRUE', 1.0, [], new \stdClass()]);
    }

    public function getFloat(): array
    {
        return $this->check('getFloat', [
            [null, null], [0, 0.0], [1, 1.0], [1.25, 1.25], ['1.25', 1.25],
            ['-0.5', -0.5], ['1e2', 100.0],
        ], [false, true, '', 'not numeric', [], new \stdClass()]);
    }

    public function getStreamed(): array
    {
        $results = $this->check('getStreamed', [
            [null, null], ['', ''], ["binary\0\xff", "binary\0\xff"],
        ], [1, false, [], new \stdClass()]);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "binary\0\xff");
        rewind($stream);
        $row = new Row(['value' => $stream], $this->validator);
        try {
            $results[] = new Result(
                $row->getStreamed('value') === $stream && ftell($stream) === 0,
                'reading the stream retains its identity and position'
            );
        } finally {
            fclose($stream);
        }
        $results[] = Assertions::throws(
            fn() => $row->getStreamed('value'),
            ValidationException::class,
            'closed stream is rejected'
        );
        return $results;
    }

    /** Checks actual return types and distinguishes missing columns from present SQL NULL. */
    private function check(string $method, array $valid, array $invalid): array
    {
        $results = [];
        foreach ($valid as [$value, $expected]) {
            $results[] = new Result(
                (new Row(['value' => $value], $this->validator))->$method('value') === $expected,
                $method . ' preserves its documented nullable type'
            );
        }
        foreach ($invalid as $value) {
            $results[] = Assertions::throws(
                fn() => (new Row(['value' => $value], $this->validator))->$method('value'),
                ValidationException::class,
                $method . ' rejects ' . get_debug_type($value)
            );
        }
        $missing = false;
        try {
            (new Row([], $this->validator))->$method('value');
        } catch (ValidationException $error) {
            $missing = str_contains($error->getMessage(), 'value')
                && $error->getExpectedValueType() === ''
                && $error->getActualValueType() === '';
        }
        $results[] = new Result($missing, $method . ' identifies a missing column');
        return $results;
    }
}
