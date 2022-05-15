<?php

namespace Lucinda\Framework\OAuth2;

use Lucinda\OAuth2\Driver;

/**
 * Binds list of \Lucinda\OAuth2\Driver instances to a list of \Lucinda\WebSecurity\Authentication\OAuth2\Driver instances
 */
class Binder
{
    /**
     * @var AbstractSecurityDriver[]
     */
    private array $results = [];

    /**
     * Kick-starts binding process
     *
     * @param array<string, Driver> $drivers
     */
    public function __construct(array $drivers)
    {
        $this->setResults($drivers);
    }

    /**
     * Performs binding process
     *
     * @param array<string, Driver> $drivers
     */
    private function setResults(array $drivers): void
    {
        foreach ($drivers as $callback=>$driver) {
            $className = str_replace(
                ["Lucinda\\OAuth2\\Vendor\\","\\Driver"],
                ["Lucinda\\Framework\\OAuth2\\", "\\SecurityDriver"],
                get_class($driver)
            );
            $this->results[] = new $className($driver, $callback);
        }
    }

    /**
     * Gets drivers found
     *
     * @return AbstractSecurityDriver[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
