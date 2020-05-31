<?php
namespace Lucinda\Framework\OAuth2\Facebook;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;

/**
 * Encapsulates operations necessary to authenticate via Facebook and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    const RESOURCE_URL = "https://graph.facebook.com/v2.8/me";
    const RESOURCE_FIELDS = array("id","name","email");
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): \Lucinda\WebSecurity\Authentication\OAuth2\UserInformation
    {
        return new UserInformation($this->driver->getResource($accessToken, self::RESOURCE_URL, self::RESOURCE_FIELDS));
    }
}
