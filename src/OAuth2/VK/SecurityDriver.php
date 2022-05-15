<?php

namespace Lucinda\Framework\OAuth2\VK;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;
use Lucinda\WebSecurity\Authentication\OAuth2\Driver;

/**
 * Encapsulates operations necessary to authenticate via VKontakte and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    public const RESOURCE_URL = "https://api.vk.com/method/users.get";

    /**
     * {@inheritDoc}
     * @see Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): UserInformation
    {
        return new UserInformation($this->driver->getResource($accessToken, self::RESOURCE_URL));
    }
}
