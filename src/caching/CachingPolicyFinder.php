<?php
require_once("CachingPolicy.php");
require_once(dirname(__DIR__)."/ClassLoader.php");

/**
 * Encapsulates detection of caching policy from a relevant XML line.
 */
class CachingPolicyFinder {
	private $policy;
	
	/**
	 * @param SimpleXMLElement $xml Tag that's holding policies.
	 * @param Application $application 
	 * @param Request $request
	 * @param Response $response
	 */
	public function __construct(SimpleXMLElement $xml, Application $application, Request $request, Response $response) {
	    $this->setPolicy($xml, $application, $request, $response);
	}
	
	/**
	 * Generates and saves a CachingPolicy object
	 *
	 * @param SimpleXMLElement $xml Tag that's holding policies.
	 * @param Application $application
	 * @param Request $request
	 * @param Response $response
	 */
	private function setPolicy(SimpleXMLElement $xml, Application $application, Request $request, Response $response) {
	    $this->policy = new CachingPolicy();
	    $this->policy->setCachingDisabled($this->getNoCache($xml));
	    $this->policy->setExpirationPeriod($this->getExpirationPeriod($xml));
	    $cacheableDriver = $this->getCacheableDriver($xml, $application, $request, $response);
	    if($cacheableDriver!==null) {
	        $this->policy->setCacheableDriver($cacheableDriver);
	    }
	}
	
	/**
	 * Gets "no_cache" property value.
	 *
     * @param SimpleXMLElement $xml Tag that's holding policies.
	 * @return NULL|boolean
	 */
	private function getNoCache(SimpleXMLElement $xml) {
		if($xml["no_cache"]===null) {
			return null;
		} else {
			return ((string) $xml["no_cache"]?true:false);
		}
	}
	
	/**
	 * Gets "expiration" property value.
	 *
     * @param SimpleXMLElement $xml Tag that's holding policies.
	 * @return number|NULL
	 */
	private function getExpirationPeriod(SimpleXMLElement $xml) {
		if($xml["expiration"]!==null) {
			return (integer) $xml["expiration"];
		}
		return null;
	}
	
	
	/**
	 * Gets CacheableDriver instance that matches "class" property value.
	 *
     * @param SimpleXMLElement $xml Tag that's holding policies.
	 * @param Application $application
	 * @param Request $request
	 * @param Response $response
	 * @return CacheableDriver|NULL
     * @throws ApplicationException If XML is invalid
     * @throws ServletException If pointed file doesn't exist or is invalid
	 */
	private function getCacheableDriver(SimpleXMLElement $xml, Application $application, Request $request, Response $response) {
		$driverClass = (string) $xml["class"];
		if($driverClass) {
		    // get cacheables folder
		    $cacheablesFolder = (string) $application->getXML()->application->paths->cacheables;
		    if(!$cacheablesFolder) throw new ApplicationException("Entry missing in configuration.xml: application.paths.cacheables");
		    
		    // loads and validates class
            load_class($cacheablesFolder, $driverClass);

			// sets driver
            $object = new $driverClass($application, $request, $response);
            if(!$object instanceof CacheableDriver) {
                throw new ServletException("Class must be instance of CacheableDriver!");
            }
            return $object;
		}		
		return null;
	}
	
	/**
	 * Gets detected caching policy
	 * 
	 * @return CachingPolicy
	 */
	public function getPolicy() {
		return $this->policy;
	}
}