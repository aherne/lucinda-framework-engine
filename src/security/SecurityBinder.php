<?php
namespace Lucinda\Framework;

require_once("PersistenceDriversDetector.php");
require_once("UserIdDetector.php");
require_once("CsrfTokenDetector.php");
require_once("Authentication.php");
require_once("Authorization.php");
require_once("oauth2/OAuth2ResourcesDriver.php");

/**
 * Binds HTTP Security API & OAuth2 Client API with MVC STDOUT API (aka Servlets API) in order to apply web security operations 
 * (eg: authentication and authorization) on a routed request
 */
class SecurityBinder {
    private $persistenceDrivers = array();
    private $oauth2Driver;
    private $userID;
    private $csrfToken;
    private $accessToken;

    /**
     * Binds APIs based on XML to perform authentication/authorization on a request
     * 
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @param string $developmentEnvironment
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
	 * @throws \Lucinda\WebSecurity\AuthenticationException If one or more persistence drivers are not instanceof PersistenceDriver
	 * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail
	 * @throws \Lucinda\MVC\STDOUT\ServletException If request doesn't come with mandatory parameters.
	 * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
	 * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, $developmentEnvironment) {
        // detects relevant data
        $xml = $application->getTag("security");
        $page = $request->getValidator()->getPage();
        $contextPath = $request->getURI()->getContextPath();
        
        // applies web security on request
        $this->setPersistenceDrivers($xml);
        $this->setUserID();
        $this->setCsrfToken($xml);
        $this->setAccessToken();
        $this->authenticate($xml, $developmentEnvironment, $page, $contextPath);
        $this->authorize($xml, $page, $contextPath);
    }

    /**
     * Sets drivers in which authenticated state will be persisted based on contents of security.persistence_driver XML tag. Supported:
     * - session: authenticated state is persisted via a secured user_id session id protected against replay through a synchronizer token bound to IP and time
     * - remember me: authenticated state is persisted via a remember me cookie also protected against replay through a synchronizer token bound to IP and time
     * - synchronizer token: authenticated state is persisted in a synchronizer token by which all future requests will be able to authenticate
     * - json web token:  authenticated state is persisted in a json web token by which all future requests will be able to authenticate
     * 
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.persistence_driver tag content)
     */
    private function setPersistenceDrivers($mainXML) {
        $pdd = new PersistenceDriversDetector($mainXML);
        $this->persistenceDrivers = $pdd->getPersistenceDrivers();
    }

    /**
     * Sets a driver able to generate or validate CSRF token required to be sent when using insecure form authentication.
     * 
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.csrf tag content)
     */
    private function setCsrfToken(\SimpleXMLElement $mainXML) {
        $this->csrfToken = new CsrfTokenDetector($mainXML);
    }
    
    /**
     * Detects access token for REST-ful sites from persistence drivers
     */
    private function setAccessToken() {
        foreach($this->persistenceDrivers as $driver) {
            if($driver instanceof \Lucinda\WebSecurity\TokenPersistenceDriver) {
                $this->accessToken = $driver->getAccessToken();
            }
        }
    }

    /**
     * Detects logged in unique user identifier from persistence drivers.
     */
    private function setUserID() {
        $udd = new UserIdDetector($this->persistenceDrivers);
        $this->userID = $udd->getUserID();
    }

    /**
     * Performs user authentication based on mechanism chosen by developmer in XML (eg: from database via login form, from an oauth2 provider, etc)
     * 
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.authentication tag content)
     * @param string $developmentEnvironment
     * @param string $page Route requested by client
     * @param string $contextPath \Lucinda\MVC\STDOUT\Application context path (default "/") necessary if multiple applications are deployed under same hostname
     */
    private function authenticate(\SimpleXMLElement $mainXML, $developmentEnvironment, $page, $contextPath) {
        new Authentication($mainXML, $developmentEnvironment, $page, $contextPath, $this->csrfToken, $this->persistenceDrivers);
        $this->oauth2Driver = new Oauth2ResourcesDriver($mainXML, $this->userID);
    }
    
    /**
     * Performs request authorization based on mechanism chosen by developmer in XML (eg: from database)
     *
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.authorization tag content)
     * @param string $page Route requested by client
     * @param string $contextPath \Lucinda\MVC\STDOUT\Application context path (default "/") necessary if multiple applications are deployed under same hostname
     */
    private function authorize(\SimpleXMLElement $mainXML, $page, $contextPath) {
        new Authorization($mainXML, $page, $contextPath, $this->userID);
    }

    /**
     * Gets detected logged in unique user identifier
     * 
     * @return integer|string
     */
    public function getUserID() {
        return $this->userID;
    }

    /**
     * Gets detected CSRF token generator / validator.
     * 
     * @return CsrfTokenDetector
     */
    public function getCsrfToken() {
        return $this->csrfToken;
    }
    
    /*
     * Gets oauth2 driver in current use to extract resources from
     *
     * @return Oauth2ResourcesDriver
     */
    public function getOAuth2Driver() {
        return $this->oauth2Driver;
    }
    
    /**
     * Gets access token to preserve/renew authentication for REST-ful sites
     *
     * @return string
     */
    public function getAccessToken() {
        return $this->accessToken;
    }
}