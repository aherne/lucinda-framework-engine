<?php
require_once("CachingPolicyLocator.php");

/**
 * Binds HTTP Caching API with MVC STDOUT API (aka Servlets API) in order to perform cache validation to a HTTP GET request and produce a response accordingly
 */
class CachingBinder {
    /**
     * @param Application $application
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Application $application, Request $request, Response $response) {
        $policy = $this->getPolicy($application, $request, $response);
        $this->validate($policy, $response);
    }
    
    /**
     * Gets caching policy that will be used for cache validation
     * 
     * @param Application $application
     * @param Request $request
     * @param Response $response
     * @return CachingPolicy
     */
    private function getPolicy(Application $application, Request $request, Response $response) {
        // detects caching_policy
        $cpb = new CachingPolicyLocator($application, $request, $response);
        $policy = $cpb->getPolicy();
        
        // create and inject driver object
        $driverClass = $policy->getCacheableDriver();
        $policy->setCacheableDriver(new $driverClass($application, $request, $response));
        
        return $policy;
    }
    
    /**
     * Performs cache validation and modifies response accordingly
     * 
     * @param CachingPolicy $policy
     * @param Response $response
     */
    private function validate(CachingPolicy $policy, Response $response) {
        if(!$policy->getCachingDisabled() && $policy->getCacheableDriver()) {
            $cacheRequest = new CacheRequest();
            if($cacheRequest->isValidatable()) {
                $validator = new CacheValidator($cacheRequest);
                $httpStatusCode = $validator->validate($policy->getCacheableDriver());
                if($httpStatusCode==304) {
                    $response->setStatus(304);
                    $response->getOutputStream()->clear();
                } else if($httpStatusCode==412) {
                    $response->setStatus(412);
                    $response->getOutputStream()->clear();
                }
            }
            $this->appendHeaders($policy, $response);
        }
    }
    
    /**
     * Append caching headers to response.
     * 
     * @param CachingPolicy $policy
     * @param Response $response
     */
    private function appendHeaders(CachingPolicy $policy, Response $response) {
        $cacheable = $policy->getCacheableDriver();
        
        $cacheResponse = new CacheResponse();
        if($cacheable->getEtag()) {
            $cacheResponse->setEtag($cacheable->getEtag());
        }
        if($cacheable->getTime()) {
            $cacheResponse->setLastModified($cacheable->getTime());
        }
        if($policy->getExpirationPeriod()) {
            $cacheResponse->setMaxAge($policy->getExpirationPeriod());
        }
        $headers = $cacheResponse->getHeaders();
        foreach($headers as $name=>$value) {
            $response->headers()->set($name, $value);
        }
    }
}
