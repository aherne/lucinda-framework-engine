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
class CacheableFinder
{
    private Cacheable $result;

    /**
     * Starts location process
     *
     * @param Application $application
     * @param Request $request
     * @param Response $response
     * @throws ConfigurationException
     */
    public function __construct(Application $application, Request $request, Response $response)
    {
        $this->setResult($application, $request, $response);
    }

    /**
     * Locates and instances a \Lucinda\Headers\Cacheable based on XML
     *
     * @param Application $application
     * @param Request $request
     * @param Response $response
     * @throws ConfigurationException
     */
    private function setResult(Application $application, Request $request, Response $response): void
    {
        $className = (string) $application->getTag("headers")["cacheable"];
        if (!$className) {
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
