<?php
namespace Lucinda\Framework;

require_once("OAuth2ResourcesDAO.php");
require_once("OAuth2ResourcesException.php");

/**
 * Driver to use in querying OAuth2 providers for resources
 */
class OAuth2ResourcesDriver
{
    private $driver;
    private $accessToken;
    
    /**
     * Detects driver to use in querying resources as well as access token based on XML and userID
     *
     * @param \SimpleXMLElement $xml
     * @param integer $userID
     */
    public function __construct($xml, $userID)
    {
        if (!$userID || !$xml->authentication->oauth2) {
            return;
        }
        
        // detect dao
        $className = (string) $xml->authentication->oauth2["dao"];
        load_class((string) $xml["dao_path"], $className);
        $daoObject = new $className();
        if (!($daoObject instanceof \Lucinda\Framework\OAuth2ResourcesDAO)) {
            return;
        }
        
        // detect driver and access token
        $driverName = $daoObject->getDriverName($this->userID);
        $driverClass = ucwords($driverName)."Driver";
        $this->driver = new $driverClass(new \OAuth2\ClientInformation(null, null, null));
        $this->accessToken = $daoObject->getAccessToken($userID);
    }
    
    /**
     * Gets resource from oauth2 provider
     *
     * @param string $url
     * @param array $fields
     * @return array
     * @throws \OAuth2\ClientException When client fails to provide mandatory parameters.
     * @throws \OAuth2\ServerException When server responds with an error.
     * @throws OAuth2ResourcesException When no valid driver or access token were detected.
     */
    public function getResource($url, $fields=array())
    {
        if (!$this->driver) {
            throw new OAuth2ResourcesException("No valid OAuth2 driver was detected in XML!");
        }
        if (!$this->accessToken) {
            throw new OAuth2ResourcesException("No access token was detected for current user!");
        }
        return $this->driver->getResource($this->accessToken, $url, $fields);
    }
}
