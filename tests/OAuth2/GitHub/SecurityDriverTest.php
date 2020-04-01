<?php
namespace Test\Lucinda\Framework\OAuth2\GitHub;

use Lucinda\Framework\OAuth2\GitHub\UserInformation;
use Lucinda\UnitTest\Result;

class SecurityDriverTest
{
    
    public function getUserInformation()
    {
        $userInformation = new UserInformation(["id"=>1, "name"=>"John Doe", "email"=>"a@a.com"]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe" && $userInformation->getEmail()=="a@a.com");
    }
    
    
}
