<?php

namespace Test\Lucinda\Framework\OAuth2\Instagram;

use Lucinda\Framework\OAuth2\Instagram\UserInformation;
use Lucinda\UnitTest\Result;

class UserInformationTest
{
    private UserInformation $userInformation;

    public function __construct()
    {
        $this->userInformation = new UserInformation(["data"=>["id"=>1, "full_name"=>"John Doe"]]);
    }

    public function getName()
    {
        return new Result($this->userInformation->getName() == "John Doe");
    }
        

    public function getEmail()
    {
        return new Result(true, "platform doesn't respond with email");
    }
        

    public function getId()
    {
        return new Result($this->userInformation->getId() == 1);
    }
}
