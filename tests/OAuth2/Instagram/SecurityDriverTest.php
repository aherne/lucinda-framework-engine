<?php
namespace Test\Lucinda\Framework\OAuth2\Instagram;

use Lucinda\Framework\OAuth2\Instagram\UserInformation;
use Lucinda\UnitTest\Result;

class SecurityDriverTest
{
    public function getUserInformation()
    {
        $userInformation = new UserInformation(["data"=>["id"=>1, "full_name"=>"John Doe"]]);
        return new Result($userInformation->getId()==1 && $userInformation->getName()=="John Doe");
    }
}
