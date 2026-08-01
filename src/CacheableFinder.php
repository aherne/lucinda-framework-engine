<?php

namespace Lucinda\Framework;

use Lucinda\Headers\Cacheable;
use Lucinda\MVC\Application;
use Lucinda\MVC\ConfigurationException;
use Lucinda\MVC\Response;
use Lucinda\STDOUT\Request;

/**
 * Locates and instances a \Lucinda\Headers\Cacheable class based on XML
 */
final class CacheableFinder
{
    private Cacheable $result;

    /**
     * Starts location process
     *
     * @param  \SimpleXMLElement $xml
     * @param  Request     $request
     * @param  Response    $response
     * @throws ConfigurationException
     */
    public function __construct(\SimpleXMLElement $xml, Request $request, Response $response)
    {
        $this->setResult($xml, $request, $response);
    }

    /**
     * Locates and instances a \Lucinda\Headers\Cacheable based on XML
     *
     * @param  \SimpleXMLElement $xml
     * @param  Request     $request
     * @param  Response    $response
     * @throws ConfigurationException
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
     * Gets \Lucinda\Headers\Cacheable instance found
     *
     * @return Cacheable
     */
    public function getResult(): Cacheable
    {
        return $this->result;
    }
}
