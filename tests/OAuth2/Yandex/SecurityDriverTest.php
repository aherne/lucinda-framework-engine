<?php

namespace Test\Lucinda\Framework\OAuth2\Yandex;

use Lucinda\Framework\OAuth2\Yandex\UserInformation;
use Lucinda\UnitTest\Result;

class SecurityDriverTest
{
    public function getUserInformation()
    {
        $userInformation = new UserInformation(["id"=>1, "first_name"=>"John", "last_name"=>"Doe", "default_email"=>"a@a.com"]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe" && $userInformation->getEmail()=="a@a.com");
    }
    public function getCallbackUrl()
    {
    }
        

    public function getAuthorizationCode()
    {
    }
        

    public function getAccessToken()
    {
    }
        

    public function getVendorName()
    {
    }
        

}
