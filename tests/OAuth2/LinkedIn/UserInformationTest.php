<?php

namespace Test\Lucinda\Framework\OAuth2\LinkedIn;

use Lucinda\Framework\OAuth2\LinkedIn\UserInformation;
use Lucinda\UnitTest\Result;

class UserInformationTest
{
    private UserInformation $userInformation;

    public function __construct()
    {
        $this->userInformation = new UserInformation(["id"=>1, "firstName"=>"John", "lastName"=>"Doe", "email"=>"a@a.com"]);
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
