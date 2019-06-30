<?php
namespace Lucinda\Framework;

require_once("oauth2/AbstractSecurityDriver.php");
require_once("oauth2/AbstractUserInformation.php");

/**
 * Detects oauth2 drivers and DAO based on contents of <oauth2> XML tag
 */
class OAuth2XMLParser {
    private $drivers = array();
    private $daoObject;
    
    /**
     * Kick-starts detection process.
     * 
     * @param \SimpleXMLElement $xml
     */
    public function __construct(\SimpleXMLElement $xml) {
        // set drivers
        $this->xml = $xml->authentication->oauth2;
        $this->setDrivers();
        
        // loads and instances DAO object
        $className = (string) $xml->authentication->oauth2["dao"];
        load_class((string) $xml["dao_path"], $className);
        $this->daoObject = new $className();
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
        
        $callbackPage = (isset($_SERVER['HTTPS'])?"https":"http")."://".$_SERVER['SERVER_NAME']."/".$callbackPage;
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
        $driverFilePath = __DIR__."/oauth2/".strtolower($driverName)."/".$driverClass.".php";
        if(!file_exists($driverFilePath)) throw new  \Lucinda\MVC\STDOUT\ServletException("Driver class not found: ".$driverFilePath);
        require_once($driverFilePath);
        $tmpClass = "\\Lucinda\\Framework\\".$driverClass;
        return new $tmpClass($clientInformation);
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
     * Gets driver that binds OAuthLogin @ Security API to OAuth2\Driver @ OAuth2Client API
     *
     * @param string $driverName Name of OAuth2 vendor (eg: facebook)
     * @throws \Lucinda\MVC\STDOUT\XMLException If vendor is not found on disk.
     * @return \Lucinda\WebSecurity\OAuth2Driver Instance that performs OAuth2 login and collects user information.
     */
    public function getLoginDriver($driverName) {
        $driverClass = $driverName."SecurityDriver";
        $driverFilePath = __DIR__."/oauth2/".strtolower($driverName)."/".$driverClass.".php";
        if(!file_exists($driverFilePath)) throw new  \Lucinda\MVC\STDOUT\ServletException("Driver class not found: ".$driverFilePath);
        require_once($driverFilePath);
        $tmpClass = "\\Lucinda\\Framework\\".$driverClass;
        return new $tmpClass($this->drivers[$driverName]);
    }
    
    /**
     * Gets oauth2 driver instance based on driver name
     * 
     * @param string $driverName
     * @return \OAuth2\Driver
     */
    public function getDriver($driverName) {
        return $this->drivers[$driverName];
    }
    
    /**
     * Gets DAO instance persisting OAuth2 authentication results into DB
     * 
     * @return \Lucinda\WebSecurity\OAuth2AuthenticationDAO
     */
    public function getDAO() {
        return $this->daoObject;
    }
}