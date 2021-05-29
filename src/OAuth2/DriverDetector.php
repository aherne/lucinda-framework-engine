<?php
namespace Lucinda\Framework\OAuth2;

use Lucinda\MVC\ConfigurationException;

/**
 * Detects current \Lucinda\OAuth2\Driver and access token for logged in user, to be used in querying provider for resources later on
 */
class DriverDetector
{
    private $accessToken;
    /**
     * @var \Lucinda\OAuth2\Driver
     */
    private $driver;
    
    /**
     * Starts detection process
     *
     * @param \SimpleXMLElement $xml
     * @param array $oauth2Drivers
     * @param string|integer $userID
     * @throws ConfigurationException
     */
    public function __construct(\SimpleXMLElement $xml, array $oauth2Drivers, $userID)
    {
        $className = (string) $xml->security->authentication->oauth2["dao"];
        if (!$className) {
            throw new ConfigurationException("Attribute 'dao' is mandatory for 'oauth2' tag");
        }
        $daoObject = new $className();
        $currentVendor = $daoObject->getVendor($userID);
        $accessToken = $daoObject->getAccessToken($userID);
        if ($currentVendor && $accessToken) {
            $this->accessToken = $accessToken;
            foreach ($oauth2Drivers as $driver) {
                if (get_class($driver) == "Lucinda\\OAuth2\\Vendor\\".$currentVendor."\\Driver") {
                    $this->driver = $driver;
                }
            }
        }
    }
    
    /**
     * Gets access token detected for current user
     *
     * @return string|NULL
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
    
    /**
     * Gets OAuth2 driver detected for current user
     *
     * @return \Lucinda\OAuth2\Driver|NULL
     */
    public function getDriver(): ?\Lucinda\OAuth2\Driver
    {
        return $this->driver;
    }
}
