<?php

namespace Lucinda\Framework\OAuth2\GitHub;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;
use Lucinda\WebSecurity\Authentication\OAuth2\Driver;

/**
 * Encapsulates operations necessary to authenticate via GitHub and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    public const RESOURCE_URL = "https://api.github.com/user";
    public const RESOURCE_URL_EMAIL = "https://api.github.com/user/emails";

    /**
     * {@inheritDoc}
     * @see Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): UserInformation
    {
        $info = $this->driver->getResource($accessToken, self::RESOURCE_URL);
        $tmp = $this->driver->getResource($accessToken, self::RESOURCE_URL_EMAIL);
        $info["email"] = $tmp[0]["email"];
        return new UserInformation($info);
    }
}
