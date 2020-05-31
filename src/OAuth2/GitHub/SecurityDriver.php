<?php
namespace Lucinda\Framework\OAuth2\GitHub;

use Lucinda\Framework\OAuth2\AbstractSecurityDriver;

/**
 * Encapsulates operations necessary to authenticate via GitHub and extract logged in user data
 */
class SecurityDriver extends AbstractSecurityDriver
{
    const RESOURCE_URL = "https://api.github.com/user";
    const RESOURCE_URL_EMAIL = "https://api.github.com/user/emails";
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\Driver::getUserInformation()
     */
    public function getUserInformation(string $accessToken): \Lucinda\WebSecurity\Authentication\OAuth2\UserInformation
    {
        $info = $this->driver->getResource($accessToken, self::RESOURCE_URL);
        $tmp = $this->driver->getResource($accessToken, self::RESOURCE_URL_EMAIL);
        $info["email"] = $tmp[0]["email"];
        return new UserInformation($info);
    }
}
