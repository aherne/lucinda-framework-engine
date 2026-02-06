<?php

namespace Test\Lucinda\Framework\OAuth2\Yandex;

use Lucinda\Framework\OAuth2\Yandex\UserInformation;
use Lucinda\UnitTest\Result;

class UserInformationTest
{
    private UserInformation $userInformation;

    public function __construct()
    {
        $this->userInformation = new UserInformation(["id"=>1, "first_name"=>"John", "last_name"=>"Doe", "default_email"=>"a@a.com"]);
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
