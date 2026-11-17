<?php

namespace Test\Lucinda\Framework\Persistence;

use Lucinda\Framework\Persistence\ThrottleBucket;
use Lucinda\UnitTest\Result;

final class ThrottleBucketTest
{
    public function getKey(): array
    {
        $key = (new ThrottleBucket('form', 'alice', '192.0.2.1'))->getKey();
        $results = [
            new Result(preg_match('/\A[0-9a-f]{64}\z/', $key) === 1, 'bucket identifier is a hexadecimal SHA-256 digest'),
            new Result($key === (new ThrottleBucket('form', 'alice', '192.0.2.1'))->getKey(), 'same identity produces a stable bucket'),
            new Result($key !== (new ThrottleBucket('mfa', 'alice', '192.0.2.1'))->getKey(), 'scope isolates failure windows'),
            new Result($key !== (new ThrottleBucket('form', 'bob', '192.0.2.1'))->getKey(), 'subject isolates failure windows'),
            new Result($key !== (new ThrottleBucket('form', 'alice', '192.0.2.2'))->getKey(), 'client address isolates failure windows'),
            new Result(
                (new ThrottleBucket('form', 'alice', '2001:db8::a'))->getKey()
                    === (new ThrottleBucket('form', 'alice', '2001:0DB8:0000:0000:0000:0000:0000:000A'))->getKey(),
                'equivalent IPv6 spellings share a failure window'
            ),
            new Result(
                (new ThrottleBucket('a:b', 'c', '192.0.2.1'))->getKey()
                    !== (new ThrottleBucket('a', 'b:c', '192.0.2.1'))->getKey(),
                'field boundaries prevent delimiter collisions'
            ),
            new Result(
                (new ThrottleBucket('form', 'alice', 'unknown'))->getKey()
                    === (new ThrottleBucket('form', 'alice', 'unknown'))->getKey(),
                'unparseable address has a stable identity'
            ),
            new Result(
                (new ThrottleBucket('form', 'alice', 'unknown'))->getKey()
                    !== (new ThrottleBucket('form', 'alice', 'other'))->getKey(),
                'distinct unparseable addresses remain separate'
            ),
        ];
        $rejected = false;
        try {
            new ThrottleBucket('form', "invalid\xff", '192.0.2.1');
        } catch (\JsonException) {
            $rejected = true;
        }
        $results[] = new Result($rejected, 'invalid UTF-8 fails explicitly instead of creating a shared bucket');
        return $results;
    }

}
