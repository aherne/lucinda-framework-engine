<?php
require_once("CachingPolicyLocator");

class CachingBinder {
    public function __construct(Application $application, Request $request, Response $response) {
        $policy = $this->getPolicy($application, $request, $response);
        $this->validate($policy, $response);
    }
    
    private function getPolicy(Application $application, Request $request, Response $response) {
        // detects caching_policy
        $cpb = new CachingPolicyLocator($application, $request, $response);
        $policy = $cpb->getPolicy();
        
        // create and inject driver object
        $driverClass = $policy->getCacheableDriver();
        $policy->setCacheableDriver(new $driverClass($application, $request, $response));
        
        return $policy;
    }
    
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
                } else {
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
        }
    }
}