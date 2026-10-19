<?php

namespace Lucinda\Framework;

use Lucinda\Headers\Cacheable;
use Lucinda\MVC\ConfigurationException;
use Lucinda\MVC\Response;
use Lucinda\STDOUT\Request;

/**
 * Resolves the cacheable implementation configured for HTTP headers
 *
 * The `cacheable` attribute must name an AbstractCacheable subclass. The
 * resolved class is instantiated with the current request and response so its
 * validators are ready when getResult() is called.
 *
 * @see AbstractCacheable
 */
final class CacheableFinder
{
    /**
     * Cache validator initialized from the configured implementation
     */
    private Cacheable $result;

    /**
     * Resolves and instantiates the configured cacheable implementation
     *
     * @param \SimpleXMLElement $xml XML element containing the `cacheable` attribute
     * @param Request $request Current request passed to the implementation
     * @param Response $response Current response passed to the implementation
     * @throws ConfigurationException If the attribute is missing or does not name an AbstractCacheable subclass
     */
    public function __construct(\SimpleXMLElement $xml, Request $request, Response $response)
    {
        $this->setResult($xml, $request, $response);
    }

    /**
     * Validates the configured class and stores its initialized instance
     *
     * @param \SimpleXMLElement $xml XML element containing the `cacheable` attribute
     * @param Request $request Current request passed to the implementation
     * @param Response $response Current response passed to the implementation
     * @throws ConfigurationException If the attribute is missing or does not name an AbstractCacheable subclass
     */
    private function setResult(\SimpleXMLElement $xml, Request $request, Response $response): void
    {
        $className = (string) $xml["cacheable"];
        if (!$className) {
            throw new ConfigurationException("Attribute 'cacheable' is mandatory for 'headers' tag");
        }
        if (!is_subclass_of($className, AbstractCacheable::class)) {
            throw new ConfigurationException("Attribute 'cacheable' is mandatory for 'headers' tag");
        }
        $this->result = new $className($request, $response);
    }

    /**
     * Gets the cacheable instance initialized during construction
     *
     * @return Cacheable Configured cache validator for the current request and response
     */
    public function getResult(): Cacheable
    {
        return $this->result;
    }
}
