<?php

namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\ServiceConverter;
use Lucinda\Framework\OAuth2\UserInfoConverter;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\mocks\OAuth2\Driver;

final class ServiceConverterTest
{
    private Driver $driver;
    private ServiceConverter $converter;

    public function __construct()
    {
        $this->driver = new Driver();
        $this->converter = new ServiceConverter($this->driver);
    }

    public function getAuthorizationCodeEndpoint()
    {
        $endpoint = $this->converter->getAuthorizationCodeEndpoint("state-123");

        return [
            new Result(
                $endpoint === "https://provider.example/authorize?state=state-123",
                "endpoint"
            ),
            new Result($this->driver->state === "state-123", "delegated state")
        ];
    }

    public function getAccessToken()
    {
        $accessToken = $this->converter->getAccessToken("authorization-code");

        return [
            new Result($accessToken === "provider-token", "access token"),
            new Result(
                $this->driver->authorizationCode === "authorization-code",
                "delegated authorization code"
            )
        ];
    }

    public function getUserInfo()
    {
        $userInfo = $this->converter->getUserInfo("provider-token");

        return [
            new Result($userInfo instanceof UserInfoConverter, "converted user information"),
            new Result($userInfo->getId() === "remote-123", "remote identifier"),
            new Result($this->driver->accessToken === "provider-token", "delegated token")
        ];
    }
}
