<?php
namespace Lucinda\Framework\OAuth2\Yandex;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;

/**
 * Encapsulates operations necessary to authenticate via Yandex and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    const RESOURCE_URL = "https://login.yandex.ru/info";
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): \Lucinda\WebSecurity\Authentication\OAuth2\UserInformation
    {
        return new UserInformation($this->driver->getResource($accessToken, self::RESOURCE_URL));
    }
}
