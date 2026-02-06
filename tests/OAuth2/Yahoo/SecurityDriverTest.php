<?php

namespace Test\Lucinda\Framework\OAuth2\Yahoo;

use Lucinda\Framework\OAuth2\Yahoo\UserInformation;
use Lucinda\Framework\OAuth2\Yahoo\SecurityDriver;
use Lucinda\OAuth2\Client\Information;
use Lucinda\OAuth2\Vendor\Yahoo\Driver;
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
        $userInformation = new UserInformation(["profile"=>["guid"=>1, "givenName"=>"John", "familyName"=>"Doe", "emails"=>["handle"=>"a@a.com"]]]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe" && $userInformation->getEmail()=="a@a.com");
    }

    public function getCallbackUrl()
    {
        return new Result($this->driver->getCallbackUrl() === "https://www.yahoo.com");
    }
        

    public function getAuthorizationCode()
    {
        $strings = new Strings($this->driver->getAuthorizationCode("wtf"));
        return $strings->assertContains("https://api.login.yahoo.com/oauth2/request_auth");
    }
        

    public function getAccessToken()
    {
        return new Result(false, "cannot be tested without a live connection");
    }
        

    public function getVendorName()
    {
        return new Result($this->driver->getVendorName() === "Yahoo");
    }
}

//["profile"=>["guid"=>1, "givenName"=>"John", "familyName"=>"Doe", "emails"=>["handle"=>"a@a.com"]]]