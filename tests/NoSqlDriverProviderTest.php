<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\NoSqlDriverProvider;
use Lucinda\NoSQL\ConfigurationException;
use Lucinda\NoSQL\ConnectionException;
use Lucinda\NoSQL\Vendor\APCu\Driver;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Fixture;

final class NoSqlDriverProviderTest
{
    public function getDriver()
    {
        $provider = new NoSqlDriverProvider(Fixture::node("nosql-single"), "local");
        $first = $provider->getDriver();
        $second = $provider->getDriver();

        $multiple = new NoSqlDriverProvider(Fixture::node("nosql-multiple"), "local");

        return [
            new Result($first instanceof Driver, "APCu driver"),
            new Result($first === $second, "driver is cached"),
            new Result($multiple->getDriver("") instanceof Driver, "unnamed APCu driver"),
            new Result(
                $multiple->getDriver("secondary") instanceof Driver,
                "named APCu driver"
            )
        ];
    }

    public function configuration()
    {
        $results = [];

        foreach (["nosql-duplicate", "nosql-missing-name", "nosql-unsupported"] as $fixture) {
            try {
                new NoSqlDriverProvider(Fixture::node($fixture), "local");
                $results[] = new Result(false, $fixture." was accepted");
            } catch (ConfigurationException) {
                $results[] = new Result(true, $fixture);
            }
        }

        $provider = new NoSqlDriverProvider(Fixture::node("empty-application"), "local");
        try {
            $provider->getDriver();
            $results[] = new Result(false, "missing data source was accepted");
        } catch (ConnectionException) {
            $results[] = new Result(true, "missing data source");
        }

        return $results;
    }
}
