<?php

namespace Test\Lucinda\Framework\OAuth2\Yahoo;

use Lucinda\Framework\OAuth2\Yahoo\UserInformation;
use Lucinda\UnitTest\Result;

class UserInformationTest
{
    private UserInformation $userInformation;

    public function __construct()
    {
        $this->userInformation = new UserInformation(["profile"=>["guid"=>1, "givenName"=>"John", "familyName"=>"Doe", "emails"=>["handle"=>"a@a.com"]]]);
    }

    public function getName()
    {
        return new Result($this->userInformation->getName() == "John Doe");
    }
        

    public function getEmail()
    {
        return new Result($this->userInformation->getEmail() == "a@a.com");
    }
        

    public function getId()
    {
        return new Result($this->userInformation->getId() == 1);
    }
}
