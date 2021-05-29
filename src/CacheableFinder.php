<?php
namespace Lucinda\Framework;

use Lucinda\MVC\ConfigurationException;

/**
 * Locates and instances a \Lucinda\Headers\Cacheable class based on XML
 */
class CacheableFinder
{
    private $result;
    
    /**
     * Starts location process
     *
     * @param \Lucinda\MVC\Application $application
     * @param \Lucinda\STDOUT\Request $request
     * @param \Lucinda\MVC\Response $response
     */
    public function __construct(\Lucinda\MVC\Application $application, \Lucinda\STDOUT\Request $request, \Lucinda\MVC\Response $response)
    {
        $this->setResult($application, $request, $response);
    }
    
    /**
     * Locates and instances a \Lucinda\Headers\Cacheable based on XML
     *
     * @param \Lucinda\MVC\Application $application
     * @param \Lucinda\STDOUT\Request $request
     * @param \Lucinda\MVC\Response $response
     * @throws \Lucinda\MVC\ConfigurationException
     */
    private function setResult(\Lucinda\MVC\Application $application, \Lucinda\STDOUT\Request $request, \Lucinda\MVC\Response $response): void
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
     * @return \Lucinda\Headers\Cacheable
     */
    public function getResult(): \Lucinda\Headers\Cacheable
    {
        return $this->result;
    }
}
