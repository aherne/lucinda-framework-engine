<?php

namespace Test\Lucinda\Framework\OAuth2\VK;

use Lucinda\Framework\OAuth2\VK\UserInformation;
use Lucinda\Framework\OAuth2\VK\SecurityDriver;
use Lucinda\OAuth2\Client\Information;
use Lucinda\OAuth2\Vendor\VK\Driver;
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
        $userInformation = new UserInformation(["response"=>[["uid"=>1, "first_name"=>"John", "last_name"=>"Doe"]]]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe");
    }

    public function getCallbackUrl()
    {
        return new Result($this->driver->getCallbackUrl() === "https://www.yahoo.com");
    }
        

    public function getAuthorizationCode()
    {
        $strings = new Strings($this->driver->getAuthorizationCode("wtf"));
        return $strings->assertContains("https://oauth.vk.com/authorize");
    }
        

    public function getAccessToken()
    {
        return new Result(false, "cannot be tested without a live connection");
    }
        

    public function getVendorName()
    {
        return new Result($this->driver->getVendorName() === "VK");
    }
}


// ["response"=>[["uid"=>1, "first_name"=>"John", "last_name"=>"Doe"]]]
