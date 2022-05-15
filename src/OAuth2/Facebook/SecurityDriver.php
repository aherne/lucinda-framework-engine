<?php

namespace Lucinda\Framework\OAuth2\Facebook;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;
use Lucinda\WebSecurity\Authentication\OAuth2\Driver;

/**
 * Encapsulates operations necessary to authenticate via Facebook and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    public const RESOURCE_URL = "https://graph.facebook.com/v2.8/me";
    public const RESOURCE_FIELDS = array("id","name","email");

    /**
     * {@inheritDoc}
     * @see Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): UserInformation
    {
        return new UserInformation($this->driver->getResource($accessToken, self::RESOURCE_URL, self::RESOURCE_FIELDS));
    }
}
