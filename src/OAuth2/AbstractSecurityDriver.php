<?php

namespace Lucinda\Framework\OAuth2;

use Lucinda\OAuth2\Client\Exception;
use Lucinda\OAuth2\Driver;
use Lucinda\WebSecurity\Authentication\OAuth2\Driver as WebSecurityDriver;

/**
 * Binds \Lucinda\OAuth2\Driver to WebSecurityDriver for OAuth2 authentication
 */
abstract class AbstractSecurityDriver implements WebSecurityDriver
{
    protected Driver $driver;
    protected string $callbackURL;

    /**
     * Registers information necessary to produce a driver later on
     *
     * @param Driver $driver
     * @param string $callbackURL
     */
    public function __construct(Driver $driver, string $callbackURL)
    {
        $this->driver = $driver;
        $this->callbackURL = $callbackURL;
    }

    /**
     * {@inheritDoc}
     * @see WebSecurityDriver::getCallbackUrl()
     */
    public function getCallbackUrl(): string
    {
        return $this->callbackURL;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     * @see WebSecurityDriver::getAuthorizationCode()
     */
    public function getAuthorizationCode(string $state): string
    {
        return $this->driver->getAuthorizationCodeEndpoint();
    }

    /**
     * {@inheritDoc}
     * @see WebSecurityDriver::getAccessToken()
     */
    public function getAccessToken(string $authorizationCode): string
    {
        $accessTokenResponse = $this->driver->getAccessToken($authorizationCode);
        // TODO: store when it expires
        return $accessTokenResponse->getAccessToken();
    }

    /**
     * {@inheritDoc}
     * @see WebSecurityDriver::getVendorName()
     */
    public function getVendorName(): string
    {
        $matches = [];
        preg_match('/Lucinda\\\\Framework\\\\OAuth2\\\\([a-zA-Z0-9]+)\\\\SecurityDriver/', get_class($this), $matches);
        return $matches[1];
    }
}
