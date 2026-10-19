<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\SqlConnectionProvider;
use Lucinda\SQL\ConfigurationException;
use Lucinda\SQL\Exception;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Fixture;

final class SqlConnectionProviderTest
{
    public function getConnection()
    {
        $provider = new SqlConnectionProvider(Fixture::node("empty-application"), "local");

        try {
            $provider->getConnection();
            return new Result(false, "missing data source was accepted");
        } catch (Exception $exception) {
            return new Result(
                $exception->getMessage() === "Datasource not set for: ",
                "missing data source"
            );
        }
    }

    public function configuration()
    {
        $results = [];

        try {
            new SqlConnectionProvider(Fixture::node("sql-single"), "local");
            $results[] = new Result(true, "single default server");
        } catch (\Throwable) {
            $results[] = new Result(false, "single default server");
        }

        foreach (["sql-duplicate", "sql-missing-name"] as $fixture) {
            try {
                new SqlConnectionProvider(Fixture::node($fixture), "local");
                $results[] = new Result(false, $fixture." was accepted");
            } catch (ConfigurationException) {
                $results[] = new Result(true, $fixture);
            }
        }

        return $results;
    }
}
