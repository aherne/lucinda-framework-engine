<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/oauth2-client/loader.php");
require_once("vendor/lucinda/security/src/authentication/OAuth2Authentication.php");
require_once("vendor/lucinda/security/src/token/TokenException.php");
require_once("AuthenticationWrapper.php");
require_once("oauth2/OAuth2XMLParser.php");
require_once("oauth2/AbstractSecurityDriver.php");
require_once("oauth2/AbstractUserInformation.php");

/**
 * Binds OAuth2Authentication @ SECURITY-API and Driver @ OAUTH2-CLIENT-API with settings from configuration.xml @ SERVLETS-API and vendor-specific
 * (eg: google / facebook) driver implementation, then performs login/logout if path requested matches paths @ xml.
 */
class OAuth2AuthenticationWrapper extends AuthenticationWrapper
{
    const DEFAULT_LOGIN_PAGE = "login";
    const DEFAULT_LOGOUT_PAGE = "logout";
    const DEFAULT_TARGET_PAGE = "index";
    
    const LOGIN_DRIVERS_PATH = "application/models/oauth2";
    
    private $xmlParser;
    private $driver;
    
    /**
     * Creates an object
     *
     * @param \SimpleXMLElement $xml XML holding information relevant to authentication (above all via security.authentication tag)
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @param string $developmentEnvironment Current development environment (eg: local)
     * @param CsrfTokenDetector $csrf Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
     * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     */
    public function __construct(\SimpleXMLElement $xml, \Lucinda\MVC\STDOUT\Request $request, $developmentEnvironment, CsrfTokenDetector $csrf, $persistenceDrivers)
    {
        $this->xmlParser = new OAuth2XMLParser($xml, $developmentEnvironment);
        $currentPage = $request->getValidator()->getPage();
        
        // loads and instances DAO object
        $daoClass = $this->xmlParser->getDaoClass();
        load_class($this->xmlParser->getDaoPath(), $daoClass);
        $daoObject = new $daoClass();
        if (!($daoObject instanceof \Lucinda\WebSecurity\Oauth2AuthenticationDAO)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of Oauth2AuthenticationDAO!");
        }
        
        // setup class properties
        $this->driver = new \Lucinda\WebSecurity\Oauth2Authentication($daoObject, $persistenceDrivers);
        
        // checks if login was requested
        $driversInformation = $this->xmlParser->getDrivers();
        foreach ($driversInformation as $driverInformation) {
            if ($driverInformation->getCallbackUrl() == $currentPage) {
                $this->login($driverInformation, $request, $csrf);
            }
        }
        
        // checks if a logout action was requested
        if ($this->xmlParser->getLogoutCallback() == $currentPage) {
            $this->logout();
        }
    }
    
    /**
     * Logs user in (and registers if not found)
     *
     * @param string $driverName Name of oauth2 driver (eg: facebook, google) that must exist as security.authentication.oauth2.{DRIVER} tag @ configuration.xml.
     * @param \SimpleXMLElement $element Object that holds XML info about driver
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @param CsrfTokenDetector $csrf Object that performs CSRF token checks.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
     * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     */
    private function login(OAuth2DriverInformation $driverInfo, \Lucinda\MVC\STDOUT\Request $request, CsrfTokenDetector $csrf)
    {
        // get oauth2 driver
        $oauth2Driver = $this->getOAuth2Driver($driverInfo, $request);
        
        // get login driver
        $loginDriver = $this->getLoginDriver($driverInfo->getDriverName(), $oauth2Driver);
        
        // detect parameters from xml
        if ($request->parameters("code")) {
            // check state
            if ($driverInfo->getDriverName() != "VK") { // hardcoding: VK sends wrong state
                if (!$request->parameters("state") || !$csrf->isValid($request->parameters("state"), 0)) {
                    throw new \Lucinda\WebSecurity\TokenException("CSRF token is invalid or missing!");
                }
            }
            
            // get access token
            $accessTokenResponse = $oauth2Driver->getAccessToken($request->parameters("code"));
            $result = $this->driver->login($loginDriver, $accessTokenResponse->getAccessToken());
            $this->setResult($result, $this->xmlParser->getLoginCallback(), $this->xmlParser->getTargetCallback());
        } elseif ($request->parameters("error")) {
            // throw exception
            $exception = new \OAuth2\ServerException($request->parameters("error"));
            $exception->setErrorCode($request->parameters("error"));
            $exception->setErrorDescription($request->parameters("error_description")?$request->parameters("error_description"):"");
            throw $exception;
        } else {
            // set result
            $scopes = array_merge($loginDriver->getDefaultScopes(), $driverInfo->getScopes());
            $result = new \Lucinda\WebSecurity\AuthenticationResult(\Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED);
            $result->setCallbackURI($oauth2Driver->getAuthorizationCodeEndpoint($scopes, $csrf->generate(0)));
            $this->result = $result;
        }
    }
    
    /**
     * Logs user out and empties all tokens for that user.
     *
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     */
    private function logout()
    {
        $result = $this->driver->logout();
        $this->setResult($result, $this->xmlParser->getLoginCallback(), $this->xmlParser->getLoginCallback());
    }
    
    /**
     * Locates, instances and returns matching \OAuth2\Driver based on info detected from XML
     * 
     * @param OAuth2DriverInformation $driverInfo Driver information detected in XML
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @return \OAuth2\Driver
     */
    private function getOAuth2Driver(OAuth2DriverInformation $driverInfo, \Lucinda\MVC\STDOUT\Request $request)
    {
        $driverName = $driverInfo->getDriverName();
        $clientInformation = new \OAuth2\ClientInformation(
            $driverInfo->getClientId(),
            $driverInfo->getClientSecret(),
            $request->getProtocol()."://".$request->getServer()->getName()."/".$driverInfo->getCallbackUrl()
            );
        $driverClass = $driverName."Driver";
        $driverFilePath = "vendor/lucinda/oauth2-client/drivers/".strtolower($driverName)."/".$driverClass.".php";
        if (!file_exists($driverFilePath)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Driver class not found: ".$driverFilePath);
        }
        require_once($driverFilePath);
        $tmpClass = "\\OAuth2\\".$driverClass;
        $object = new $tmpClass($clientInformation);
        if (!($object instanceof \OAuth2\Driver)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of \OAuth2\Driver: ".$className);
        }
        if ($driverName == "GitHub") {
            $object->setApplicationName($driverInfo->getApplicationName());
        }
        return $object;
    }
    
    /**
     * Locates, instances and returns matching \Lucinda\WebSecurity\OAuth2Driver based on \OAuth2\Driver detected before
     * 
     * @param string $driverName Name of OAuth2 vendor
     * @param \OAuth2\Driver $driverInstance Driver detected beforehand
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @return \Lucinda\WebSecurity\OAuth2Driver
     */
    private function getLoginDriver($driverName, \OAuth2\Driver $driverInstance)
    {
        $driverClass = $driverName."SecurityDriver";
        $driverFilePath = self::LOGIN_DRIVERS_PATH."/".strtolower($driverName)."/".$driverClass.".php";
        if (!file_exists($driverFilePath)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Driver class not found: ".$driverFilePath);
        }
        require_once($driverFilePath);
        $object = new $driverClass($driverInstance);
        if (!($object instanceof \Lucinda\WebSecurity\OAuth2Driver)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of \Lucinda\WebSecurity\OAuth2Driver: ".$className);
        }
        return $object;
    }
}
