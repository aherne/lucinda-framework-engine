<?php

namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\UserInfoConverter;
use Lucinda\OAuth2\UserInfo;
use Lucinda\UnitTest\Result;

final class UserInfoConverterTest
{
    private UserInfoConverter $converter;

    public function __construct()
    {
        $this->converter = new UserInfoConverter(
            new UserInfo("remote-123", "Jane Doe", "jane@example.com")
        );
    }

    public function getId()
    {
        return new Result($this->converter->getId() === "remote-123");
    }

    public function getName()
    {
        return new Result($this->converter->getName() === "Jane Doe");
    }

    public function getEmail()
    {
        return new Result($this->converter->getEmail() === "jane@example.com");
    }
}
