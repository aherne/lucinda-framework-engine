<?php
namespace Lucinda\Framework;

require("PersistenceDriversDetector.php");
require("UserIdDetector.php");
require("CsrfTokenDetector.php");
require("Authentication.php");
require("Authorization.php");
require("oauth2/OAuth2ResourcesDriver.php");

/**
 * Binds HTTP Security API & OAuth2 Client API with MVC STDOUT API (aka Servlets API) in order to apply web security operations
 * (eg: authentication and authorization) on a routed request
 */
class SecurityBinder
{
    protected $ipAddress;
    protected $persistenceDrivers = array();
    protected $oauth2Driver;
    protected $userID;
    protected $csrfToken;
    protected $accessToken;
    
    /**
     * Binds APIs based on XML to perform authentication/authorization on a request
     *
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @param string $developmentEnvironment
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
     * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, $developmentEnvironment)
    {
        // detects relevant data
        $xml = $application->getTag("security");
        
        // applies web security on request
        $this->setIpAddress($request);
        $this->setPersistenceDrivers($xml);
        $this->setUserID();
        $this->setCsrfToken($xml);
        $this->setAccessToken();
        $this->authenticate($xml, $request, $developmentEnvironment);
        $this->authorize($xml, $request);
    }
    
    protected function setIpAddress(\Lucinda\MVC\STDOUT\Request $request) {
        $this->ipAddress = $request->getClient()->getIP();
    }
    
    /**
     * Sets drivers in which authenticated state will be persisted based on contents of security.persistence_driver XML tag. Supported:
     * - session: authenticated state is persisted via a secured user_id session id protected against replay through a synchronizer token bound to IP and time
     * - remember me: authenticated state is persisted via a remember me cookie also protected against replay through a synchronizer token bound to IP and time
     * - synchronizer token: authenticated state is persisted in a synchronizer token by which all future requests will be able to authenticate
     * - json web token:  authenticated state is persisted in a json web token by which all future requests will be able to authenticate
     *
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.persistence_driver tag content)
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    protected function setPersistenceDrivers($mainXML)
    {
        $pdd = new PersistenceDriversDetector($mainXML, $this->ipAddress);
        $this->persistenceDrivers = $pdd->getPersistenceDrivers();
    }
    
    /**
     * Sets a driver able to generate or validate CSRF token required to be sent when using insecure form authentication.
     *
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.csrf tag content)
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    protected function setCsrfToken(\SimpleXMLElement $mainXML)
    {
        $this->csrfToken = new CsrfTokenDetector($mainXML, $this->ipAddress);
    }
    
    /**
     * Detects access token for REST-ful sites from persistence drivers
     */
    protected function setAccessToken()
    {
        foreach ($this->persistenceDrivers as $driver) {
            if ($driver instanceof \Lucinda\WebSecurity\TokenPersistenceDriver) {
                $this->accessToken = $driver->getAccessToken();
            }
        }
    }
    
    /**
     * Detects logged in unique user identifier from persistence drivers.
     */
    protected function setUserID()
    {
        $udd = new UserIdDetector($this->persistenceDrivers);
        $this->userID = $udd->getUserID();
    }
    
    /**
     * Performs user authentication based on mechanism chosen by developmer in XML (eg: from database via login form, from an oauth2 provider, etc)
     *
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.authentication tag content)
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @param string $developmentEnvironment
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
     * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    protected function authenticate(\SimpleXMLElement $mainXML, \Lucinda\MVC\STDOUT\Request $request, $developmentEnvironment)
    {
        new Authentication($mainXML, $request, $developmentEnvironment, $this->ipAddress, $this->csrfToken, $this->persistenceDrivers);
        $this->oauth2Driver = new Oauth2ResourcesDriver($mainXML, $this->userID);
    }
    
    /**
     * Performs request authorization based on mechanism chosen by developmer in XML (eg: from database)
     *
     * @param \SimpleXMLElement $mainXML XML holding relevant information (above all via security.authorization tag content)
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws SecurityPacket If authorization encounters a situation where execution cannot continue and redirection is required
     */
    protected function authorize(\SimpleXMLElement $mainXML, \Lucinda\MVC\STDOUT\Request $request)
    {
        new Authorization($mainXML, $request, $this->userID);
    }
    
    /**
     * Gets IP address detected from HTTP headers sent by client or REMOTE_ADDR header received from server.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }
    
    /**
     * Gets detected logged in unique user identifier
     *
     * @return integer|string
     */
    public function getUserID()
    {
        return $this->userID;
    }
    
    /**
     * Gets detected CSRF token generator / validator.
     *
     * @return CsrfTokenDetector
     */
    public function getCsrfToken()
    {
        return $this->csrfToken;
    }
    
    /*
     * Gets oauth2 driver in current use to extract resources from
     *
     * @return Oauth2ResourcesDriver
     */
    public function getOAuth2Driver()
    {
        return $this->oauth2Driver;
    }
    
    /**
     * Gets access token to preserve/renew authentication for REST-ful sites
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
