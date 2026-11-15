<?php

namespace Test\Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\StoredValue;
use Lucinda\Framework\NoSQL\StoredValueBuilder;
use Lucinda\UnitTest\Result;

/**
 * Verifies logical record lifetime, safe payloads and exact wire round trips
 */
final class StoredValueTest
{
    /**
     * Checks logical revisions survive encoding and decoding
     *
     * @return Result Revision round-trip assertion
     */
    public function getVersion(): Result
    {
        $stored = new StoredValue('version', 0.0, false);
        $value = (new StoredValueBuilder($stored->encode()))->build();
        return new Result($value->getVersion() === 'version', 'wire revision');
    }

    /**
     * Checks deadline precision survives encoding and decoding
     *
     * @return Result Deadline round-trip assertion
     */
    public function getExpiresAt(): Result
    {
        $stored = new StoredValue('version', 1234.125, false);
        $value = (new StoredValueBuilder($stored->encode()))->build();
        return new Result($value->getExpiresAt() === 1234.125, 'wire deadline');
    }

    /**
     * Checks binary and typed application values survive the wire format
     *
     * @return list<Result> Payload round-trip assertions
     */
    public function getValue(): array
    {
        $results = [];
        foreach ([null, false, 0, "binary\0\xff", ['id' => 12]] as $payload) {
            $stored = new StoredValue('version', 0.0, $payload);
            $value = (new StoredValueBuilder($stored->encode()))->build();
            $results[] = new Result(
                $value->getValue() === $payload,
                'exact wire payload'
            );
        }
        return $results;
    }

    /**
     * Requires expired records and deletion tombstones to be logically absent
     *
     * @return list<Result> Logical lifetime assertions
     */
    public function isLive(): array
    {
        return [
            new Result((new StoredValue('v', 0.0, 1))->isLive(), 'no expiry'),
            new Result(
                !(new StoredValue('v', microtime(true) - 1, 1))->isLive(),
                'expired record'
            ),
            new Result(
                !(new StoredValue('v', 0.0, null, true))->isLive(),
                'tombstone'
            ),
        ];
    }

    /**
     * Rejects invalid metadata and payloads before storage encoding
     *
     * @return list<Result> Invalid-value assertions
     */
    public function validate(): array
    {
        $results = [];
        $values = [
            new StoredValue('', 0.0, null),
            new StoredValue('v', -1.0, null),
            new StoredValue('v', INF, null),
            new StoredValue('v', 0.0, new \stdClass()),
        ];
        foreach ($values as $value) {
            $rejected = false;
            try {
                $value->validate();
            } catch (\InvalidArgumentException) {
                $rejected = true;
            }
            $results[] = new Result($rejected, 'invalid atomic record');
        }
        return $results;
    }

    /**
     * Requires unsupported objects to fail rather than invoke serialization
     *
     * @return Result Encoding-boundary assertion
     */
    public function encode(): Result
    {
        $rejected = false;
        try {
            (new StoredValue('v', 0.0, new \stdClass()))->encode();
        } catch (\InvalidArgumentException) {
            $rejected = true;
        }
        return new Result($rejected, 'object payload rejected before encoding');
    }
}
