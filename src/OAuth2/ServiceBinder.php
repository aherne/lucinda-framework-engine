<?php

namespace Lucinda\Framework\OAuth2;

use Lucinda\OAuth2\Driver as OAuth2ClientDriver;
use Lucinda\WebSecurity\OAuth2Service;

/**
 * Adapts OAuth2 client drivers into services consumed by web security
 *
 * Each driver is exposed under the final segment of its class name, matching
 * the provider names used by security configuration. Drivers that resolve to
 * the same name replace earlier entries.
 *
 * @see ServiceConverter
 */
final class ServiceBinder
{
    /**
     * @var array<string,OAuth2Service> Adapted services keyed by provider class name
     */
    private array $services=[];

    /**
     * Converts the supplied OAuth2 client drivers into security services
     *
     * @param OAuth2ClientDriver[] $oauth2Drivers Provider drivers to adapt
     */
    public function __construct(array $oauth2Drivers)
    {
        foreach ($oauth2Drivers as $driver) {
            $vendorName = $this->getVendorName($driver);

            if (isset($this->services[$vendorName])) {
                throw new \LogicException("Duplicate OAuth2 provider: ".$vendorName);
            }

            $this->services[$vendorName] = $this->convert($driver);
        }
    }

    /**
     * Gets the provider name represented by a driver's first class name segment
     *
     * @param OAuth2ClientDriver $driver Driver whose provider name is required
     * @return string Final namespace segment of the concrete driver class
     */
    private function getVendorName(OAuth2ClientDriver $driver): string
    {
        return str_replace(
            ["Lucinda\\OAuth2\\Vendor\\", "\\Driver"],
            "",
            get_class($driver)
        );
    }

    /**
     * Wraps an OAuth2 client driver in the web-security service contract
     *
     * @param OAuth2ClientDriver $driver Provider driver to adapt
     * @return OAuth2Service Security-compatible view of the driver
     */
    private function convert(OAuth2ClientDriver $driver): OAuth2Service
    {
        return new ServiceConverter($driver);
    }

    /**
     * Gets all adapted provider services
     *
     * @return array<string,OAuth2Service> Services keyed by provider class name
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
