<?php

namespace Test\Lucinda\Framework\Persistence\Queries;

use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;

final class ParametersValidatorTest
{
    public function validate(): array
    {
        $validator = new ParametersValidator();
        $results = [];
        $valid = [
            [true, DataType::BOOLEAN, true], [false, DataType::BOOLEAN, false],
            [0, DataType::BOOLEAN, false], [1, DataType::BOOLEAN, true],
            ['0', DataType::BOOLEAN, false], ['1', DataType::BOOLEAN, true],
            ['true', DataType::BOOLEAN, true], ['false', DataType::BOOLEAN, false],
            [null, DataType::NULLABLE, null],
            ['', DataType::STRING, ''], ["bytes\0\xff", DataType::STRING, "bytes\0\xff"],
            [42, DataType::STRING, '42'], [1.25, DataType::STRING, '1.25'],
        ];
        foreach ([0, -1, PHP_INT_MIN, PHP_INT_MAX] as $integer) {
            $valid[] = [$integer, DataType::INTEGER, $integer];
            $valid[] = [(string) $integer, DataType::INTEGER, $integer];
        }
        foreach ($valid as [$value, $type, $expected]) {
            $results[] = new Result($validator->validate($value, $type) === $expected, $type->name . ' normalization');
        }
        $invalid = [
            [null, DataType::INTEGER], [true, DataType::INTEGER], [false, DataType::INTEGER],
            [1.0, DataType::INTEGER], ['1.25', DataType::INTEGER], ['1e2', DataType::INTEGER],
            [(string) PHP_INT_MAX . '0', DataType::INTEGER],
            [2, DataType::BOOLEAN], ['TRUE', DataType::BOOLEAN], [1.0, DataType::BOOLEAN],
            [0, DataType::NULLABLE], ['0', DataType::NULLABLE], [false, DataType::NULLABLE],
            [false, DataType::STRING], [null, DataType::STRING], [[], DataType::STRING],
            [new \stdClass(), DataType::STRING], ['bytes', DataType::STREAMED], [null, DataType::STREAMED],
        ];
        foreach ($invalid as [$value, $type]) {
            $results[] = Assertions::throws(fn() => $validator->validate($value, $type), ValidationException::class, $type->name . ' rejects invalid input');
        }
        $stream = fopen('php://memory', 'r+');
        try {
            fwrite($stream, 'value');
            rewind($stream);
            $results[] = new Result(
                $validator->validate($stream, DataType::STREAMED) === $stream && ftell($stream) === 0,
                'stream input is not consumed or replaced'
            );
        } finally {
            fclose($stream);
        }
        $results[] = Assertions::throws(fn() => $validator->validate($stream, DataType::STREAMED), ValidationException::class, 'closed resource rejected');
        try {
            $validator->validate([], DataType::INTEGER);
        } catch (ValidationException $error) {
            $results[] = new Result(
                $error->getExpectedValueType() === 'integer' && $error->getActualValueType() === 'array',
                'validation failure retains expected and actual type metadata'
            );
        }
        return $results;
    }
}
