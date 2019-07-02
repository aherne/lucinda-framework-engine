<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/oauth2-client/loader.php");
require_once("vendor/lucinda/security/src/authentication/OAuth2Authentication.php");
require_once("vendor/lucinda/security/src/token/TokenException.php");
require_once("AuthenticationWrapper.php");
require_once("OAuth2XMLParser.php");

/**
 * Binds OAuth2Authentication @ SECURITY-API and Driver @ OAUTH2-CLIENT-API with settings from configuration.xml @ SERVLETS-API and vendor-specific 
 * (eg: google / facebook) driver implementation, then performs login/logout if path requested matches paths @ xml.
 */
class OAuth2AuthenticationWrapper extends AuthenticationWrapper {
	const DEFAULT_LOGIN_PAGE = "login";
	const DEFAULT_LOGOUT_PAGE = "logout";
	const DEFAULT_TARGET_PAGE = "index";
	
	private $parser;
	private $xml;
	private $authentication;
	
	/**
	 * Creates an object
	 * 
	 * @param \SimpleXMLElement $xml Contents of security.authentication.oauth2 tag @ configuration.xml.
	 * @param string $currentPage Current page requested.
	 * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers List of drivers to persist information across requests.
	 * @param CsrfTokenDetector $csrf Object that performs CSRF token checks.
	 * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
	 * @throws \Lucinda\WebSecurity\AuthenticationException If one or more persistence drivers are not instanceof PersistenceDriver
	 * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
	 * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
	 */
	public function __construct(\SimpleXMLElement $xml, $currentPage, $persistenceDrivers, CsrfTokenDetector $csrf) {
	    $this->xml = $xml->authentication->oauth2;
	    $this->parser = new OAuth2XMLParser($xml);
		
		// loads and instances DAO object
		$daoObject = $this->parser->getDAO();
		if(!($daoObject instanceof \Lucinda\WebSecurity\Oauth2AuthenticationDAO)) throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of Oauth2AuthenticationDAO!");
		
		// setup class properties
		$this->authentication = new \Lucinda\WebSecurity\Oauth2Authentication($daoObject, $persistenceDrivers);

		// checks if a login action was requested, in which case it forwards
		$xmlLocal = $this->xml->driver;
		foreach($xmlLocal as $element) {
			$driverName = (string) $element["name"];
			$callbackPage = (string) $element["callback"];
			if($callbackPage == $currentPage) {
				$this->login($driverName, $element, $csrf);
			}
		}

		// checks if a logout action was requested, in which case it forwards
		$logoutPage = (string) $this->xml["logout"];
		if(!$logoutPage) $logoutPage = self::DEFAULT_LOGOUT_PAGE;
		if($logoutPage == $currentPage) {
			$this->logout();
		}
	}
	
	/**
	 * Logs user in (and registers if not found)
	 * 
	 * @param string $driverName Name of oauth2 driver (eg: facebook, google) that must exist as security.authentication.oauth2.{DRIVER} tag @ configuration.xml.
	 * @param \SimpleXMLElement $element Object that holds XML info about driver
	 * @param CsrfTokenDetector $csrf Object that performs CSRF token checks. 
	 * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
	 * @throws \Lucinda\WebSecurity\AuthenticationException If one or more persistence drivers are not instanceof PersistenceDriver
	 * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
	 * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
	 */
	private function login($driverName, $element, CsrfTokenDetector $csrf) {
		// detect class and load file
		$loginDriver = $this->parser->getLoginDriver($driverName);

		// detect parameters from xml
		$authorizationCode = (!empty($_GET["code"])?$_GET["code"]:"");
		if($authorizationCode) {
			$targetSuccessPage = (string) $this->xml["target"];
			if(!$targetSuccessPage) $targetSuccessPage = self::DEFAULT_TARGET_PAGE;
			$targetFailurePage = (string) $this->xml["login"];
			if(!$targetFailurePage) $targetFailurePage = self::DEFAULT_LOGIN_PAGE;

			// check state
			if($driverName != "VK") { // hardcoding: VK sends wrong state
				if(empty($_GET['state']) || !$csrf->isValid($_GET['state'], 0)) {
				    throw new \Lucinda\WebSecurity\TokenException("CSRF token is invalid or missing!");
				}	
			}
			
			// get access token
			$accessTokenResponse = $this->parser->getDriver($driverName)->getAccessToken($_GET["code"]);
			
			// get 
			$result = $this->authentication->login($loginDriver, $accessTokenResponse->getAccessToken());
			$this->setResult($result, $targetFailurePage, $targetSuccessPage);
		} else {
			// get scopes
		    $targetScopes = $loginDriver->getDefaultScopes();
			$scopes = (string) $element["scopes"];
			if($scopes) $targetScopes = array_merge($targetScopes, explode(",",$scopes));
		
			// set result
			$result = new \Lucinda\WebSecurity\AuthenticationResult(\Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED);
			$result->setCallbackURI($this->parser->getDriver($driverName)->getAuthorizationCodeEndpoint($targetScopes, $csrf->generate(0)));
			$this->result = $result;
		}
	}
	
	/**
	 * Logs user out and empties all tokens for that user.
	 * 
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 */
	private function logout() {
		$loginPage = (string) $this->xml["login"];
		if(!$loginPage) $loginPage = self::DEFAULT_LOGIN_PAGE;
		
		$result = $this->authentication->logout();
		$this->setResult($result, $loginPage, $loginPage);
	}
}