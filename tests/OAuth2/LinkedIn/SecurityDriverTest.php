<?php
namespace Test\Lucinda\Framework\OAuth2\LinkedIn;

use Lucinda\Framework\OAuth2\LinkedIn\UserInformation;
use Lucinda\UnitTest\Result;

class SecurityDriverTest
{
    public function getUserInformation()
    {
        $userInformation = new UserInformation(["id"=>1, "firstName"=>"John", "lastName"=>"Doe", "email"=>"a@a.com"]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe" && $userInformation->getEmail()=="a@a.com");
    }
}
