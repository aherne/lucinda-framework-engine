<?php
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/http-caching/loader.php");
require_once(str_replace("/test/","/src/",__FILE__));

require_once(dirname(__DIR__)."/application.php");
require_once(dirname(__DIR__)."/request.php");
require_once(dirname(__DIR__)."/response.php");

// instance application object
$application = new Application("configuration.xml");

// check global caching
echo "GLOBAL SETTINGS:\n";
$request = new Request();
$request->setValidator(new PageValidator("index", $application));
$response = new Response("text/html");
$response->getOutputStream()->write("asd");
$test = new CachingPolicyBinder($application, $request, $response);
echo "getCachingDisabled: ".($test->getPolicy()->getCachingDisabled()?"NOK":"OK")."\n";
echo "getExpirationPeriod: ".($test->getPolicy()->getExpirationPeriod()==100?"OK":"NOK")."\n";
echo "getCacheableDriver: ".(get_class($test->getPolicy()->getCacheableDriver())=="TestCacheable"?"OK":"NOK")."\n";

// check route-based caching
echo "ROUTE-BASED SETTINGS:\n";
$_SERVER["REQUEST_URI"] = "/login";
$request = new Request();
$request->setValidator(new PageValidator("login", $application));
$response = new Response("text/html");
$response->getOutputStream()->write("fgh");
$test = new CachingPolicyBinder($application, $request, $response);
echo "getCachingDisabled: ".($test->getPolicy()->getCachingDisabled()?"OK":"NOK")."\n";
echo "getExpirationPeriod: ".($test->getPolicy()->getExpirationPeriod()==100?"OK":"NOK")."\n";
echo "getCacheableDriver: ".(get_class($test->getPolicy()->getCacheableDriver())=="TestCacheable"?"OK":"NOK");