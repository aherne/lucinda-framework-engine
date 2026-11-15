<?php

namespace Test\Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\NoSQL\KeyNotFoundException;
use Lucinda\UnitTest\Result;

/**
 * Verifies snapshot absence, exact values and immutable private metadata
 */
final class VersionedValueTest
{
    /**
     * Checks logical keys are preserved without native storage metadata
     *
     * @return Result Logical identity assertion
     */
    public function getKey(): Result
    {
        $value = new VersionedValue('logical:key', null, null, 0.0);
        return new Result($value->getKey() === 'logical:key', 'logical key');
    }

    /**
     * Distinguishes absent observations from existing null and false values
     *
     * @return list<Result> Existence assertions
     */
    public function exists(): array
    {
        return [
            new Result(
                !(new VersionedValue('key', null, null, 0.0))->exists(),
                'explicit absence'
            ),
            new Result(
                (new VersionedValue('key', null, 'v1', 0.0))->exists(),
                'stored null exists'
            ),
            new Result(
                (new VersionedValue('key', false, 'v2', 0.0))->exists(),
                'stored false exists'
            ),
        ];
    }

    /**
     * Checks exact payload types and missing-value errors
     *
     * @return list<Result> Payload assertions
     */
    public function getValue(): array
    {
        $results = [];
        foreach ([null, false, 0, "binary\0\xff", ['id' => 12]] as $payload) {
            $value = new VersionedValue('key', $payload, 'v1', 0.0);
            $results[] = new Result(
                $value->getValue() === $payload,
                'exact payload type'
            );
        }
        $missing = false;
        try {
            (new VersionedValue('key', null, null, 0.0))->getValue();
        } catch (KeyNotFoundException) {
            $missing = true;
        }
        $results[] = new Result($missing, 'absent value throws');
        return $results;
    }

    /**
     * Checks opaque logical revisions and absent revision metadata
     *
     * @return list<Result> Revision assertions
     */
    public function getVersion(): array
    {
        return [
            new Result(
                (new VersionedValue('key', 1, 'opaque', 0.0))->getVersion()
                    === 'opaque',
                'opaque version'
            ),
            new Result(
                (new VersionedValue('key', null, null, 0.0))->getVersion()
                    === null,
                'absence has no revision'
            ),
        ];
    }

    /**
     * Checks deadline precision used by non-extending counter updates
     *
     * @return Result Original expiration assertion
     */
    public function getExpiresAt(): Result
    {
        $value = new VersionedValue('key', 1, 'v1', 1234.125);
        return new Result(
            $value->getExpiresAt() === 1234.125,
            'exact deadline'
        );
    }

    /**
     * Requires readonly final snapshots with no public metadata fields
     *
     * @return Result Snapshot encapsulation assertion
     */
    public function encapsulation(): Result
    {
        $class = new \ReflectionClass(VersionedValue::class);
        $private = true;
        foreach ($class->getProperties() as $property) {
            $private = $private && $property->isPrivate();
        }
        return new Result(
            $class->isFinal() && $class->isReadOnly() && $private,
            'final readonly object with private fields'
        );
    }
}
