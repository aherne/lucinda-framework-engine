<?php
namespace Lucinda\Framework;

/**
 * Encapsulates commands necessary to extract name and access token of OAuth2 provider in current use.
 */
interface OAuth2ResourcesDAO
{
    /**
     * Gets OAuth2 driver name (eg: facebook) for current logged in user.
     *
     * @param integer $userID
     * @return string
     */
    public function getDriverName($userID);
    
    /**
     * Gets OAuth2 access token for current logged in user
     *
     * @param integer $userID
     * @return string
     */
    public function getAccessToken($userID);
}
