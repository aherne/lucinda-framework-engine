<?php

namespace Lucinda\Framework\OAuth2\LinkedIn;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;
use Lucinda\WebSecurity\Authentication\OAuth2\Driver;

/**
 * Encapsulates operations necessary to authenticate via LinkedIn and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    public const RESOURCE_URL = "https://api.linkedin.com/v1/people/~";
    public const RESOURCE_URL_EMAIL = "https://api.linkedin.com/v1/people/~/email-address";

    /**
     * {@inheritDoc}
     * @see Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): UserInformation
    {
        $info = $this->driver->getResource($accessToken, self::RESOURCE_URL);
        $info["email"] = $this->driver->getResource($accessToken, self::RESOURCE_URL_EMAIL);
        return new UserInformation($info);
    }
}
