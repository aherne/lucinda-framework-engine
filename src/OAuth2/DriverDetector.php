<?php

namespace Lucinda\Framework\OAuth2;

use Lucinda\MVC\ConfigurationException;
use Lucinda\OAuth2\Driver;

/**
 * Detects current \Lucinda\OAuth2\Driver and access token for logged in user, to be used in querying provider for resources later on
 */
class DriverDetector
{
    private ?string $accessToken = null;
    private ?Driver $driver = null;

    /**
     * Starts detection process
     *
     * @param  \SimpleXMLElement     $xml
     * @param  array<string, Driver> $oauth2Drivers
     * @param  string|int            $userID
     * @throws ConfigurationException
     */
    public function __construct(\SimpleXMLElement $xml, array $oauth2Drivers, string|int $userID)
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
     * Gets resource from OAuth2 vendor
     *
     * @param  string   $url
     * @param  string[] $fields
     * @return array<mixed>
     * @throws \Lucinda\OAuth2\Client\Exception
     * @throws \Lucinda\OAuth2\Server\Exception
     */
    public function getResource(string $url, array $fields=[]): array
    {
        return $this->driver->getResource($this->accessToken, $url, $fields);
    }
}
