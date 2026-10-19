<?php

namespace Test\Lucinda\Framework\mocks\OAuth2;

use Lucinda\OAuth2\AccessToken\Response as AccessTokenResponse;
use Lucinda\OAuth2\Driver as BaseDriver;
use Lucinda\OAuth2\ResponseWrapper;
use Lucinda\OAuth2\UserInfo;
use Lucinda\OAuth2\UserInfo\Extractor;
use Lucinda\OAuth2\UserInfo\Requester;

final class Driver extends BaseDriver
{
    public string $state = "";
    public string $authorizationCode = "";
    public string $accessToken = "";

    public function __construct()
    {
    }

    public function getAuthorizationCodeEndpoint(string $state = ""): string
    {
        $this->state = $state;

        return "https://provider.example/authorize?state=".$state;
    }

    public function getAccessToken(string $authorizationCode): AccessTokenResponse
    {
        $this->authorizationCode = $authorizationCode;

        return new AccessTokenResponse(["access_token" => "provider-token"]);
    }

    public function getUserInfo(string $accessToken): UserInfo
    {
        $this->accessToken = $accessToken;

        return new UserInfo("remote-123", "Jane Doe", "jane@example.com");
    }

    protected function getResponseWrapper(): ResponseWrapper
    {
        throw new \LogicException("Not used by this test double");
    }

    protected function getUserInfoRequester(): Requester
    {
        throw new \LogicException("Not used by this test double");
    }

    protected function getUserInfoExtractor(): Extractor
    {
        throw new \LogicException("Not used by this test double");
    }
}
