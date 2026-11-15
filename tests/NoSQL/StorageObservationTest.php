<?php

namespace Test\Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\StorageObservation;
use Lucinda\UnitTest\Result;

/**
 * Verifies exact preservation of internal native observation metadata
 */
final class StorageObservationTest
{
    /**
     * Checks physical keys remain distinct from public logical identities
     *
     * @return Result Native key assertion
     */
    public function getKey(): Result
    {
        $value = new StorageObservation('physical:key', null, null);
        return new Result($value->getKey() === 'physical:key', 'physical key');
    }

    /**
     * Checks raw binary bytes and native absence are not conflated
     *
     * @return list<Result> Native value assertions
     */
    public function getValue(): array
    {
        return [
            new Result(
                (new StorageObservation('key', "x\0\xff", 't'))->getValue()
                    === "x\0\xff",
                'binary bytes'
            ),
            new Result(
                (new StorageObservation('key', null, null))->getValue()
                    === null,
                'native absence'
            ),
        ];
    }

    /**
     * Checks vendor token types are preserved without coercion
     *
     * @return list<Result> Native token assertions
     */
    public function getToken(): array
    {
        $results = [];
        foreach ([null, 12, 12.5, 'opaque'] as $token) {
            $value = new StorageObservation('key', null, $token);
            $results[] = new Result(
                $value->getToken() === $token,
                'token type'
            );
        }
        return $results;
    }
}
