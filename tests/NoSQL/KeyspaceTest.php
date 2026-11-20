<?php

namespace Test\Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\Keyspace;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Assertions;

final class KeyspaceTest
{
    public function getKey(): array
    {
        $space = new Keyspace('application:');
        $first = $space->getKey('session', 'identity');
        return [
            new Result($first === $space->getKey('session', 'identity'), 'same keyspace and identity are stable'),
            new Result(str_starts_with($first, 'application:session:'), 'application and purpose remain explicit'),
            new Result($first !== $space->getKey('lock', 'identity'), 'purpose isolates storage domains'),
            new Result($first !== $space->getKey('session', 'other'), 'identity isolates records'),
            new Result($first !== (new Keyspace('other:'))->getKey('session', 'identity'), 'application namespaces isolate records'),
            new Result(!str_contains($space->getKey('session', "arbitrary\0\xff"), "\0"), 'arbitrary identity bytes are hashed outside structural key text'),
            Assertions::throws(fn() => new Keyspace(''), \InvalidArgumentException::class, 'empty namespace rejected'),
        ];
    }
}
