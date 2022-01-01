<?php
namespace Lucinda\Framework\OAuth2\Yahoo;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;
use Lucinda\WebSecurity\Authentication\OAuth2\Driver;

/**
 * Encapsulates operations necessary to authenticate via Yahoo and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    const RESOURCE_URL = "https://social.yahooapis.com/v1/user/me/profile";
    
    /**
     * {@inheritDoc}
     * @see Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): UserInformation
    {
        return new UserInformation($this->driver->getResource($accessToken, self::RESOURCE_URL));
    }
}
