<?php
namespace Lucinda\Framework\OAuth2;

/**
 * Interface implementing blueprints for detecting current OAuth2 provider info from DB for current logged in user
 */
interface UserDAO
{
    /**
     * Gets current access token from DB for current logged in user
     * 
     * @param integer|string $userID
     * @return string|NULL
     */
    function getAccessToken($userID): ?string;
    
    /**
     * Gets name of OAuth2 vendor from DB for current logged in user
     * 
     * @param integer|string $userID
     * @return string|NULL
     */
    function getVendor($userID): ?string;
}