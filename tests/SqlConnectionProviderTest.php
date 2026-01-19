<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\SqlConnectionProvider;
use Lucinda\SQL\Connection;
use Lucinda\STDOUT\Application;
use Lucinda\UnitTest\Result;

class SqlConnectionProviderTest
{
    public function getConnection()
    {
        $application = new Application(__DIR__."/mocks/stdout.xml");
        $provider = new SqlConnectionProvider($application->getXML(), ENVIRONMENT);
        $connection = $provider->getConnection("");
        return new Result($connection instanceof Connection);
    }
}
