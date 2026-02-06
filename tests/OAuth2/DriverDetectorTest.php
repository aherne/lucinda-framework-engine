<?php
namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\UnitTest\Result;

class DriverDetectorTest
{
    public function getResource()
    {
        return new Result(false, "cannot be tested without a live connection");
    }
}
