<?php

namespace Test\Lucinda\Framework\OAuth2\Yahoo;

use Lucinda\Framework\OAuth2\Yahoo\UserInformation;
use Lucinda\UnitTest\Result;

class SecurityDriverTest
{
    public function getUserInformation()
    {
        $userInformation = new UserInformation(["profile"=>["guid"=>1, "givenName"=>"John", "familyName"=>"Doe", "emails"=>["handle"=>"a@a.com"]]]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe" && $userInformation->getEmail()=="a@a.com");
    }
}
