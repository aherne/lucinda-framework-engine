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
     * @param \Lucinda\STDOUT\Response $response
     */
    public function __construct(\Lucinda\STDOUT\Application $application, \Lucinda\STDOUT\Request $request, \Lucinda\STDOUT\Response $response)
    {
        $this->setResult($application, $request, $response);
    }
    
    /**
     * Locates and instances a \Lucinda\Headers\Cacheable based on XML
     *
     * @param \Lucinda\STDOUT\Application $application
     * @param \Lucinda\STDOUT\Request $request
     * @param \Lucinda\STDOUT\Response $response
     * @throws \Lucinda\STDOUT\XMLException
     */
    private function setResult(\Lucinda\STDOUT\Application $application, \Lucinda\STDOUT\Request $request, \Lucinda\STDOUT\Response $response): void
    {
        $cacheableClass = (string) $application->getTag("headers")["cacheable"];
        if (!$cacheableClass) {
            throw new \Lucinda\STDOUT\XMLException("No 'cacheable' attribute was found in 'headers' tag");
        }
        $cacheablesFolder = (string) $application->getTag("application")->paths["cacheables"];
        $finder = new \Lucinda\STDOUT\Locators\ClassFinder($cacheablesFolder);
        $className = $finder->find($cacheableClass);
        $this->result = new $className($request, $response);
        if (!($this->result instanceof AbstractCacheable)) {
            throw new \Lucinda\STDOUT\Exception("Class must implement: \\Lucinda\\Framework\\AbstractCacheable");
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
