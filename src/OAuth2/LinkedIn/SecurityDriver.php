<?php
namespace Lucinda\Framework\OAuth2\LinkedIn;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;

/**
 * Encapsulates operations necessary to authenticate via LinkedIn and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    const RESOURCE_URL = "https://api.linkedin.com/v1/people/~";
    const RESOURCE_URL_EMAIL = "https://api.linkedin.com/v1/people/~/email-address";
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): UserInformation
    {
        $info = $this->driver->getResource($accessToken, self::RESOURCE_URL);
        $info["email"] = $this->driver->getResource($accessToken, self::RESOURCE_URL_EMAIL);
        return new UserInformation($info);
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getVendorName()
     */
    public function getVendorName(): string
    {
        return "LinkedIn";
    }
}
