<?php

namespace Test\Lucinda\Framework;

use Lucinda\NoSQL\Driver;
use Lucinda\Framework\NoSqlDriverProvider;
use Lucinda\STDOUT\Application;
use Lucinda\UnitTest\Result;

class NoSqlDriverProviderTest
{
    public function getDriver()
    {
        $application = new Application(__DIR__."/mocks/stdout.xml");
        $provider = new NoSqlDriverProvider($application->getXML(), ENVIRONMENT);
        $driver = $provider->getDriver("");
        return new Result($driver instanceof Driver);
    }
}
