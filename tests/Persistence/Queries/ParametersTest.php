<?php

namespace Test\Lucinda\Framework\Persistence\Queries;

use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;

final class ParametersTest
{
    public function set(): array
    {
        $parameters = new Parameters(new ParametersValidator());
        $results = [new Result($parameters->set(':id', '12', DataType::INTEGER) === $parameters, 'set supports fluent typed bindings')];
        $parameters->set(':flag', 'false', DataType::BOOLEAN)
            ->set(':empty', null, DataType::NULLABLE)
            ->set(':text', 1.25, DataType::STRING);
        $results[] = new Result($parameters->getStorage() === [
            ':id' => ['value' => 12, 'type' => DataType::INTEGER],
            ':flag' => ['value' => false, 'type' => DataType::BOOLEAN],
            ':empty' => ['value' => null, 'type' => DataType::NULLABLE],
            ':text' => ['value' => '1.25', 'type' => DataType::STRING],
        ], 'storage contains normalized values and their explicit types');
        $results[] = Assertions::throws(fn() => $parameters->set(':id', 'wrong', DataType::INTEGER), ValidationException::class, 'invalid replacement fails');
        $results[] = new Result($parameters->getStorage()[':id']['value'] === 12, 'failed validation preserves an existing binding');
        $parameters->set(':id', 13, DataType::INTEGER);
        $results[] = new Result($parameters->getStorage()[':id']['value'] === 13, 'valid replacement updates the binding');
        return $results;
    }

    public function getStorage(): array
    {
        $parameters = new Parameters();
        $results = [new Result($parameters->getStorage() === [], 'new parameter set is empty')];
        $stream = fopen('php://memory', 'r+');
        try {
            $parameters->set(':stream', $stream, DataType::STREAMED);
            $snapshot = $parameters->getStorage();
            $results[] = new Result($snapshot[':stream'] === ['value' => $stream, 'type' => DataType::STREAMED], 'stream and explicit type survive storage');
            unset($snapshot[':stream']);
            $results[] = new Result(isset($parameters->getStorage()[':stream']), 'editing returned storage does not remove a retained binding');
        } finally {
            fclose($stream);
        }
        return $results;
    }
}
