<?php

namespace Lucinda\Framework\OAuth2;

use Lucinda\WebSecurity\OAuth2Service as DestinationDriver;
use Lucinda\OAuth2\Driver as SourceDriver;
use Lucinda\WebSecurity\DAO\OAuth2\UserInformation;

/**
 * Adapts an OAuth2 client driver to the web-security provider contract
 *
 * Authorization URL and user-information operations are delegated directly.
 * Access-token responses and provider user information are reduced to the
 * narrower representations required by the security workflow.
 *
 * @see DestinationDriver
 */
final class ServiceConverter implements DestinationDriver
{
    /**
     * OAuth2 client driver receiving delegated provider operations
     */
    private SourceDriver $driver;

    /**
     * Wraps an OAuth2 client driver for use by web security
     *
     * @param SourceDriver $driver Configured provider driver to adapt
     */
    public function __construct(SourceDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Builds the provider authorization endpoint for a new login attempt
     *
     * @param string $state State value that the provider must return unchanged
     * @return string Absolute provider authorization URL
     * @throws \Throwable If the client driver cannot build the authorization URL
     */
    public function getAuthorizationCodeEndpoint(string $state): string
    {
        return $this->driver->getAuthorizationCodeEndpoint($state);
    }

    /**
     * Exchanges an authorization code and extracts its access-token value
     *
     * @param string $authorizationCode Authorization code returned by the provider
     * @return string Provider access token from the client's token response
     * @throws \Throwable If the code exchange or response parsing fails
     */
    public function getAccessToken(string $authorizationCode): string
    {
        return $this->driver->getAccessToken($authorizationCode)->getAccessToken();
    }

    /**
     * Loads and adapts user information from the OAuth2 provider
     *
     * @param string $accessToken Provider access token authorizing the request
     * @return UserInformation Provider identity in the web-security DAO contract
     * @throws \Throwable If the provider request or response conversion fails
     */
    public function getUserInfo(string $accessToken): UserInformation
    {
        $userInformation = $this->driver->getUserInfo($accessToken);
        return new UserInfoConverter($userInformation);
    }
}
