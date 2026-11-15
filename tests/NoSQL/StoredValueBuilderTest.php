<?php

namespace Test\Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\StoredValue;
use Lucinda\Framework\NoSQL\StoredValueBuilder;
use Lucinda\NoSQL\OperationFailedException;
use Lucinda\UnitTest\Result;

/**
 * Requires corrupt records to fail instead of becoming missing values
 */
final class StoredValueBuilderTest
{
    /**
     * Exercises valid records and malformed wire representations
     *
     * @return list<Result> Record-decoding assertions
     */
    public function build(): array
    {
        $stored = new StoredValue('v', 0.0, ['binary' => "x\0\xff"]);
        $decoded = (new StoredValueBuilder($stored->encode()))->build();
        $results = [new Result(
            $decoded->getValue() === ['binary' => "x\0\xff"],
            'valid binary record'
        )];
        $record = json_decode($stored->encode(), true);
        $badPayload = $record;
        $badPayload['payload'] = 'invalid base64!';
        $badMetadata = $record;
        $badMetadata['version'] = '';
        $objectPayload = $record;
        $objectPayload['payload'] = base64_encode(serialize(new \stdClass()));
        $sources = [
            'not JSON',
            '{}',
            json_encode($badPayload),
            json_encode($badMetadata),
            json_encode($objectPayload),
        ];
        foreach ($sources as $source) {
            $rejected = false;
            try {
                (new StoredValueBuilder($source))->build();
            } catch (OperationFailedException) {
                $rejected = true;
            }
            $results[] = new Result($rejected, 'malformed record rejected');
        }
        return $results;
    }
}
