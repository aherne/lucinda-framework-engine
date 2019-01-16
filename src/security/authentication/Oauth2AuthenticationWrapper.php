<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/oauth2-client/loader.php");
require_once("vendor/lucinda/security/src/authentication/Oauth2Authentication.php");
require_once("vendor/lucinda/security/src/token/TokenException.php");
require_once("AuthenticationWrapper.php");

/**
 * Binds OAuth2Authentication @ SECURITY-API and Driver @ OAUTH2-CLIENT-API with settings from configuration.xml @ SERVLETS-API and vendor-specific 
 * (eg: google / facebook) driver implementation, then performs login/logout if path requested matches paths @ xml.
 */
class Oauth2AuthenticationWrapper extends AuthenticationWrapper {
    const DRIVERS_LOCATION = "application/models/oauth2";

	const DEFAULT_LOGIN_PAGE = "login";
	const DEFAULT_LOGOUT_PAGE = "logout";
	const DEFAULT_TARGET_PAGE = "index";
	
	private $xml;
	private $authentication;
	private $drivers = array();
	
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
		// set drivers
		$this->xml = $xml->authentication->oauth2;
		$this->setDrivers();
		
		// loads and instances DAO object
		$className = (string) $xml->authentication->oauth2["dao"];
		load_class((string) $xml["dao_path"], $className);
		$daoObject = new $className();
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
		$loginDriver = $this->getLoginDriver($driverName);

		// detect parameters from xml
		$authorizationCode = (!empty($_GET["code"])?$_GET["code"]:"");
		if($authorizationCode) {
			$targetSuccessPage = (string) $this->xml["target"];
			if(!$targetSuccessPage) $targetSuccessPage = self::DEFAULT_TARGET_PAGE;
			$targetFailurePage = (string) $this->xml["login"];
			if(!$targetFailurePage) $targetFailurePage = self::DEFAULT_LOGIN_PAGE;
			$createIfNotExists = (integer) $this->xml["auto_create"];
		
			// check state
			if($driverName != "VK") { // hardcoding: VK sends wrong state
				if(empty($_GET['state']) || !$csrf->isValid($_GET['state'], 0)) {
				    throw new \Lucinda\WebSecurity\TokenException("CSRF token is invalid or missing!");
				}	
			}
			
			// get access token
			$accessTokenResponse = $this->drivers[$driverName]->getAccessToken($_GET["code"]);
			
			// get 
			$result = $this->authentication->login($loginDriver, $accessTokenResponse->getAccessToken(), $createIfNotExists);
			$this->setResult($result, $targetFailurePage, $targetSuccessPage);
		} else {
			// get scopes
			$scopes = (string) $element["scopes"];
			if($scopes) $targetScopes = explode(",",$scopes);
			else $targetScopes = $loginDriver->getDefaultScopes();
		
			// set result
			$result = new \Lucinda\WebSecurity\AuthenticationResult(\Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED);
			$result->setCallbackURI($this->drivers[$driverName]->getAuthorizationCodeEndpoint($targetScopes, $csrf->generate(0)));
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
	
	/**
	 * Builds an oauth2 client information object based on contents of security.authentication.oauth2.{DRIVER} tag @ configuration.xml.
	 * 
	 * @param \SimpleXMLElement $xml Contents of security.authentication.oauth2.{DRIVER} tag @ configuration.xml.
	 * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
	 * @return \OAuth2\ClientInformation Encapsulates information about client that must match that in oauth2 remote server.
	 */
	private function getClientInformation(\SimpleXMLElement $xml) {
		// get client id and secret from xml
		$clientID = (string) $xml["client_id"];
		$clientSecret = (string) $xml["client_secret"];
		if(!$clientID || !$clientSecret) throw new \Lucinda\MVC\STDOUT\XMLException("Tags 'client_id' and 'client_secret' are mandatory for 'driver' subtag of 'oauth2' tag");
		
		// callback page is same as driver login page
		$callbackPage = (string) $xml["callback"];
		if(!$callbackPage) throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'callback' is mandatory for 'driver' subtag of 'oauth2' tag");
		
		$callbackPage = (isset($_SERVER['HTTPS'])?"https":"http")."://".$_SERVER['HTTP_HOST']."/".$callbackPage;
		return new \OAuth2\ClientInformation($clientID, $clientSecret, $callbackPage);
	}
	
	/**
	 * Gets driver to interface OAuth2 operations with @ OAuth2Client API
	 * 
	 * @param string $driverName Name of OAuth2 vendor (eg: facebook)
	 * @param \OAuth2\ClientInformation $clientInformation Object that encapsulates application credentials
	 * @throws \Lucinda\MVC\STDOUT\XMLException If vendor is not found on disk.
	 * @return \OAuth2\Driver Instance of driver that abstracts OAuth2 operations.
	 */
	private function getAPIDriver($driverName, \OAuth2\ClientInformation $clientInformation) {
		$driverClass = $driverName."Driver";
		$driverFilePath = self::DRIVERS_LOCATION."/".$driverName."/".$driverClass.".php";
		if(!file_exists($driverFilePath)) throw new  \Lucinda\MVC\STDOUT\ServletException("Driver class not found: ".$driverFilePath);
		require_once($driverFilePath);
		return new $driverClass($clientInformation);
	}
	
	/**
	 * Gets driver that binds OAuthLogin @ Security API to OAuth2\Driver @ OAuth2Client API
	 * 
	 * @param string $driverName Name of OAuth2 vendor (eg: facebook)
	 * @throws \Lucinda\MVC\STDOUT\XMLException If vendor is not found on disk.
	 * @return \Lucinda\WebSecurity\OAuth2Driver Instance that performs OAuth2 login and collects user information.
	 */
	private function getLoginDriver($driverName) {
		$driverClass = $driverName."SecurityDriver";
		$driverFilePath = self::DRIVERS_LOCATION."/".$driverName."/".$driverClass.".php";
		if(!file_exists($driverFilePath)) throw new  \Lucinda\MVC\STDOUT\ServletException("Driver class not found: ".$driverFilePath);
		require_once($driverFilePath);
		return new $driverClass($this->drivers[$driverName]);
	}
	
	/**
	 * Sets OAuth2\Driver instances based on XML
	 *
	 * @throws \Lucinda\MVC\STDOUT\XMLException If required tags aren't found in XML / do not reflect on disk
	 */
	private function setDrivers() {
		$xmlLocal = $this->xml->driver;
		foreach($xmlLocal as $element) {
			$driverName = (string) $element["name"];
			if(!$driverName) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'name' is mandatory for 'driver' subtag of oauth2 tag");
		
			$clientInformation = $this->getClientInformation($element);
			$this->drivers[$driverName] = $this->getAPIDriver($driverName, $clientInformation);
			if($driverName == "GitHub") {
				$applicationName = (string) $element["application_name"];
				if(!$applicationName) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'application_name' of 'driver' subtag of 'oauth2' tag is mandatory for GitHub");
				$this->drivers[$driverName]->setApplicationName($applicationName);
			}
		}
	}
	
	/**
	 * Gets OAuth2 drivers
	 * 
	 * @return \OAuth2\Driver[string] List of available oauth2 drivers by driver name.
	 */
	public function getDrivers() {
		return $this->drivers;
	}
}