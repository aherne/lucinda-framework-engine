<?php

namespace Test\Lucinda\Framework\OAuth2\GitHub;

use Lucinda\Framework\OAuth2\GitHub\UserInformation;
use Lucinda\Framework\OAuth2\GitHub\SecurityDriver;
use Lucinda\OAuth2\Client\Information;
use Lucinda\OAuth2\Vendor\GitHub\Driver;
use Lucinda\UnitTest\Result;
use Lucinda\UnitTest\Validator\Strings;

class SecurityDriverTest
{
    private SecurityDriver $driver;

    public function __construct()
    {
        $driver = new Driver(new Information("que", "rty", "https://www.foo.bar"), ["ghj"]);
        $this->driver = new SecurityDriver($driver,  "https://www.yahoo.com");
    }

    public function getUserInformation()
    {
        $userInformation = new UserInformation(["id"=>1, "name"=>"John Doe", "email"=>"a@a.com"]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe" && $userInformation->getEmail()=="a@a.com");
    }

    public function getCallbackUrl()
    {
        return new Result($this->driver->getCallbackUrl() === "https://www.yahoo.com");
    }
        

    public function getAuthorizationCode()
    {
        $strings = new Strings($this->driver->getAuthorizationCode("wtf"));
        return $strings->assertContains("https://github.com/login/oauth/authorize");
    }
        

    public function getAccessToken()
    {
        return new Result(false, "cannot be tested without a live connection");
    }
        

    public function getVendorName()
    {
        return new Result($this->driver->getVendorName() === "GitHub");
    }
}
