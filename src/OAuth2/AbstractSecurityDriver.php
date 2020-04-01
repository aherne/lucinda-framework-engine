<?php
namespace Lucinda\Framework\OAuth2;

/**
 * Binds \Lucinda\OAuth2\Driver to \Lucinda\WebSecurity\Authentication\OAuth2\Driver for OAuth2 authentication
 */
abstract class AbstractSecurityDriver implements \Lucinda\WebSecurity\Authentication\OAuth2\Driver
{
    protected $driver;
    protected $callbackURL;
    
    /**
     * Registers information necessary to produce a driver later on
     * 
     * @param \Lucinda\OAuth2\Driver $driver
     * @param string $callbackURL
     */
    public function __construct(\Lucinda\OAuth2\Driver $driver, string $callbackURL)
    {
        $this->driver = $driver;
        $this->callbackURL = $callbackURL;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getCallbackUrl()
     */
    public function getCallbackUrl(): string
    {
        return $this->callbackURL;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getAuthorizationCode()
     */
    public function getAuthorizationCode(string $state): string
    {
        return $this->driver->getAuthorizationCodeEndpoint();
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getAccessToken()
     */
    public function getAccessToken(string $authorizationCode): string
    {
        $accessTokenResponse = $this->driver->getAccessToken($authorizationCode);
        // TODO: store when it expires
        return $accessTokenResponse->getAccessToken();
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getVendorName()
     */
    public function getVendorName(): string
    {
        $matches = [];
        preg_match('/Lucinda\\\\Framework\\\\OAuth2\\\\([a-zA-Z0-9]+)\\\\SecurityDriver/', get_class($this), $matches);
        return $matches[1];
    }
}

