<?php
namespace Test\Lucinda\Framework\OAuth2\VK;

use Lucinda\Framework\OAuth2\VK\UserInformation;
use Lucinda\UnitTest\Result;

class SecurityDriverTest
{
    
    public function getUserInformation()
    {
        $userInformation = new UserInformation(["response"=>[["uid"=>1, "first_name"=>"John", "last_name"=>"Doe"]]]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe");
    }
    
    
}
