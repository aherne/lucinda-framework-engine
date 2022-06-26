<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\LoggingRequestInformation;
use Lucinda\UnitTest\Result;

class LoggingRequestInformationTest
{
    public function getRequestInformation()
    {
        $_SERVER = [
            "argv"=>["test.php", "testing"]
        ];
        $information = new LoggingRequestInformation();
        return new Result($information->getRequestInformation()->getUri()=="testing");
    }
}