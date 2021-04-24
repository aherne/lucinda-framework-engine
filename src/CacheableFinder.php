<?php
namespace Lucinda\Framework;

/**
 * Locates and instances a \Lucinda\Headers\Cacheable class based on XML
 */
class CacheableFinder
{
    private $result;
    
    /**
     * Starts location process
     *
     * @param \Lucinda\STDOUT\Application $application
     * @param \Lucinda\STDOUT\Request $request
     * @param \Lucinda\MVC\Response $response
     */
    public function __construct(\Lucinda\STDOUT\Application $application, \Lucinda\STDOUT\Request $request, \Lucinda\MVC\Response $response)
    {
        $this->setResult($application, $request, $response);
    }
    
    /**
     * Locates and instances a \Lucinda\Headers\Cacheable based on XML
     *
     * @param \Lucinda\STDOUT\Application $application
     * @param \Lucinda\STDOUT\Request $request
     * @param \Lucinda\MVC\Response $response
     * @throws \Lucinda\MVC\ConfigurationException
     */
    private function setResult(\Lucinda\STDOUT\Application $application, \Lucinda\STDOUT\Request $request, \Lucinda\MVC\Response $response): void
    {
        $cacheableClass = (string) $application->getTag("headers")["cacheable"];
        if (!$cacheableClass) {
            throw new \Lucinda\MVC\ConfigurationException("No 'cacheable' attribute was found in 'headers' tag");
        }
        $finder = new \Lucinda\STDOUT\Locators\ClassFinder("");
        $className = $finder->find($cacheableClass);
        $this->result = new $className($request, $response);
        if (!($this->result instanceof AbstractCacheable)) {
            throw new \Lucinda\MVC\ConfigurationException("Class must implement: \\Lucinda\\Framework\\AbstractCacheable");
        }
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
