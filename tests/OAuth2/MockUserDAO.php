<?php

namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\UserDAO;

class MockUserDAO implements UserDAO
{
    public function getVendor($userID): ?string
    {
        return __CLASS__;
    }

    public function getAccessToken($userID): ?string
    {
        return "qwerty";
    }
}
