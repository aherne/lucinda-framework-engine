<?php
namespace Lucinda\Framework;
require_once("CachingPolicyFinder.php");

/**
 * Locates CachingPolicy in XML based on contents of http_caching tag. Binds route-based settings (if any) with
 * global caching settings into a CachingPolicy object.
 */
class CachingPolicyLocator {
    private $policy;

    /**
     * CachingPolicyBinder constructor.
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response) {
        $this->setPolicy($application, $request, $response);
    }

    /**
     * Detects caching policy based on contents of http_caching tag and sets a CachingPolicy object in result
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is incorrect formatted.
     */
    private function setPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response) {
        $this->policy = $this->getGlobalPolicy($application, $request, $response);
        $specificPolicy = $this->getSpecificPolicy($application, $request, $response);
        if($specificPolicy) {
            if($specificPolicy->getCachingDisabled()!==null) {
                $this->policy->setCachingDisabled($specificPolicy->getCachingDisabled());
            }
            if($specificPolicy->getExpirationPeriod()!==null) {
                $this->policy->setExpirationPeriod($specificPolicy->getExpirationPeriod());
            }
            if($specificPolicy->getCacheableDriver()!==null) {
                $this->policy->setCacheableDriver($specificPolicy->getCacheableDriver());
            }
        }
    }

    /**
     * Detects generic CachingPolicy (applying by default to all routes)
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is incorrect formatted.
     */
    private function getGlobalPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response) {
        $caching = $application->getTag("http_caching");
        if(!$caching) throw new \Lucinda\MVC\STDOUT\XMLException("Entry missing in configuration.xml: http_caching");
        $finder = new CachingPolicyFinder($caching, $application, $request, $response);
        return $finder->getPolicy();
    }

    /**
     * Detects route-specific CachingPolicy (if any)
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is incorrect formatted.
     */
    private function getSpecificPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response) {
        $page = $request->getValidator()->getPage();
        $tmp = (array) $application->getTag("http_caching");
        if(!empty($tmp["route"])) {
            $elements = is_array($tmp["route"])?$tmp["route"]:array($tmp["route"]);
            foreach($elements as $info) {
                $route = $info["url"];
                if($route === null) throw new \Lucinda\MVC\STDOUT\XMLException("Property missing in http_caching.route tag: url");
                if($route == $page) {
                    $finder = new CachingPolicyFinder($info, $application, $request, $response);
                    return $finder->getPolicy();
                }
            }
        }
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