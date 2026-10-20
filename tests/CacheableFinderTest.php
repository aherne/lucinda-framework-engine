<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\CacheableFinder;
use Lucinda\MVC\ConfigurationException;
use Lucinda\MVC\Response\Http;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\mocks\Cacheable;
use Test\Lucinda\Framework\Support\Fixture;

final class CacheableFinderTest
{
    public function getResult()
    {
        $request = Fixture::webRequest();
        $response = new Http();
        $cacheable = (new CacheableFinder(
            Fixture::node("headers-valid"),
            $request,
            $response
        ))->getResult();

        return [
            new Result($cacheable instanceof Cacheable, "configured class"),
            new Result($cacheable->getTime() === 1700000000, "last-modified value"),
            new Result(
                $cacheable->getEtag() === sha1("articles#1700000000"),
                "etag value"
            )
        ];
    }

    public function invalidConfiguration()
    {
        $request = Fixture::webRequest();
        $response = new Http();
        $results = [];

        foreach (["headers-missing-cacheable", "headers-invalid-cacheable"] as $fixture) {
            try {
                new CacheableFinder(Fixture::node($fixture), $request, $response);
                $results[] = new Result(false, $fixture." was accepted");
            } catch (ConfigurationException) {
                $results[] = new Result(true, $fixture);
            }
        }

        return $results;
    }
}
