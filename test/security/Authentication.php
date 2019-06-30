<?php
set_include_path(dirname(dirname(__DIR__)));
require_once(dirname(dirname(__DIR__))."/src/ClassLoader.php");
require_once(dirname(dirname(__DIR__))."/src/security/CsrfTokenDetector.php");
require_once(dirname(dirname(__DIR__))."/src/security/PersistenceDriversDetector.php");

$xml = simplexml_load_file("configuration.xml");
$xml = $xml->security;

$csrf = new Lucinda\Framework\CsrfTokenDetector($xml);

$pdd = new Lucinda\Framework\PersistenceDriversDetector($xml);
$persistenceDrivers = $pdd->getPersistenceDrivers();

/**
 * Form Authentication
 */
require_once(dirname(dirname(__DIR__))."/src/security/authentication/DAOAuthenticationWrapper.php");

// no authentication requested
$authentication = new Lucinda\Framework\DAOAuthenticationWrapper($xml, "login", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()==null?"Y":"N")."\n";

// login failed
$_POST = array("username"=>"lucian", "password"=>"epopescu", "csrf" => $csrf->generate(0));
$authentication = new Lucinda\Framework\DAOAuthenticationWrapper($xml, "login", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_FAILED?"Y":"N")."\n";

// login success
$_POST = array("username"=>"lucian", "password"=>"popescu", "csrf" => $csrf->generate(0));
$authentication = new Lucinda\Framework\DAOAuthenticationWrapper($xml, "login", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_OK?"Y":"N")."\n";

// logout success
$authentication = new Lucinda\Framework\DAOAuthenticationWrapper($xml, "logout", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::LOGOUT_OK?"Y":"N")."\n";

// logout failed
$authentication = new Lucinda\Framework\DAOAuthenticationWrapper($xml, "logout", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::LOGOUT_FAILED?"Y":"N")."\n";

/**
 * OAuth2 Authentication
 */

// test XML parser
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/security/src/authentication/OAuth2AuthenticationDAO.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/security/src/authentication/OAuth2UserInformation.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/security/src/authentication/OAuth2Driver.php");
require_once(dirname(dirname(__DIR__))."/src/security/authentication/OAuth2XMLParser.php");
require_once(dirname(dirname(__DIR__))."/src/security/oauth2/OAuth2ResourcesDAO.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/oauth2-client/src/ClientInformation.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/oauth2-client/src/ResponseWrapper.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/oauth2-client/src/Driver.php");
$_SERVER["SERVER_NAME"] = "www.test.com";
$_SERVER["HTTPS"] = 1;
$xmlParser = new Lucinda\Framework\OAuth2XMLParser($xml);
echo __LINE__.": ".($xmlParser->getDAO() instanceof UsersOAuth2Authentication?"Y":"N")."\n";
echo __LINE__.": ".($xmlParser->getDriver("Facebook") instanceof \Lucinda\Framework\FacebookDriver?"Y":"N")."\n";
echo __LINE__.": ".($xmlParser->getLoginDriver("Facebook") instanceof \Lucinda\Framework\FacebookSecurityDriver?"Y":"N")."\n";

require_once(dirname(dirname(__DIR__))."/src/security/authentication/OAuth2AuthenticationWrapper.php");

// no authentication requested
$authentication = new Lucinda\Framework\OAuth2AuthenticationWrapper($xml, "login", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()==null?"Y":"N")."\n";

// login requested
$authentication = new Lucinda\Framework\OAuth2AuthenticationWrapper($xml, "login/facebook", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED?"Y":"N")."\n";

// authorization code received, asking for a token
$_GET["code"] = "test";
$_GET["state"] = $csrf->generate(0);
try {
    new Lucinda\Framework\OAuth2AuthenticationWrapper($xml, "login/facebook", $persistenceDrivers, $csrf);
    echo __LINE__.": N\n";
} catch(Exception $e) {
    echo __LINE__.": ".($e instanceof \OAuth2\ServerException && $e->getMessage()=="Missing or invalid client id."?"Y":"N")."\n";
}
$persistenceDrivers[0]->save(1);

// logout success
$authentication = new Lucinda\Framework\OAuth2AuthenticationWrapper($xml, "logout", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::LOGOUT_OK?"Y":"N")."\n";

// logout failed
$authentication = new Lucinda\Framework\OAuth2AuthenticationWrapper($xml, "logout", $persistenceDrivers, $csrf);
echo __LINE__.": ".($authentication->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::LOGOUT_FAILED?"Y":"N")."\n";


/**
 * OAuth2 resources retrieval 
 */
require_once(dirname(dirname(__DIR__))."/src/security/oauth2/OAuth2ResourcesDriver.php");
$resources = new Lucinda\Framework\OAuth2ResourcesDriver($xml, 0);
try {
    $data = $resources->getResource("test/me");
    echo __LINE__.": N\n";
} catch(Lucinda\Framework\OAuth2ResourcesException $e) {
    echo __LINE__.": ".$e->getMessage()==("No access token was detected for current user!"?"Y":"N")."\n";
}

