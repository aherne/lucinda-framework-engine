<?php
namespace Lucinda\Framework;

require_once("OAuth2DriverInformation.php");

/**
 * Detects oauth2 information based on contents of <oauth2> XML tag
 */
class OAuth2XMLParser
{
    const DEFAULT_LOGIN_PAGE = "login";
    const DEFAULT_LOGOUT_PAGE = "logout";
    const DEFAULT_TARGET_PAGE = "index";
    
    private $xml;
    private $drivers = array();
    private $daoClass;
    private $daoPath;
    private $loginCallback;
    private $logoutCallback;
    private $targetCallback;
    
    /**
     * Kick-starts detection process.
     *
     * @param \SimpleXMLElement $xml
     * @param string $developmentEnvironment
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    public function __construct(\SimpleXMLElement $xml, $developmentEnvironment)
    {
        // set drivers
        $this->xml = $xml->authentication->oauth2->{$developmentEnvironment};
        if (!$this->xml) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Missing 'driver' subtag of '".$developmentEnvironment."', child of 'oauth2' tag");
        }
        $this->setDrivers();
        $this->setDaoClass($xml);
        $this->setDaoPath($xml);
        $this->setLoginCallback($xml);
        $this->setLogoutCallback($xml);
        $this->setTargetCallback($xml);
    }
    
    /**
     * Sets oauth2 drivers information based on XML
     *
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    private function setDrivers()
    {
        $xmlLocal = (array) $this->xml;
        if (empty($xmlLocal["driver"])) {
            return;
        }
        $list = (is_array($xmlLocal["driver"])?$xmlLocal["driver"]:[$xmlLocal["driver"]]);
        foreach ($list as $element) {
            $information = new OAuth2DriverInformation($element);
            $this->drivers[$information->getDriverName()] = $information;
        }        
    }
    
    /**
     * Sets name of DAO class based on XML
     * 
     * @param \SimpleXMLElement $xml Pointer to <security> tag.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    private function setDaoClass(\SimpleXMLElement $xml)
    {
        $this->daoClass = (string) $xml->authentication->oauth2["dao"];
        if (!$this->daoClass) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'dao' is mandatory for 'oauth2' tag!");
        }
    }
    
    /**
     * Sets folder in which DAO class resides based on XML
     *
     * @param \SimpleXMLElement $xml Pointer to <security> tag.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    private function setDaoPath(\SimpleXMLElement $xml)
    {
        $this->daoPath = (string) $xml["dao_path"];
        if (!$this->daoPath) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'dao_path' is mandatory for 'security' tag when 'oauth2' tag is also defined!");
        }
    }
    
    /**
     * Sets callback URL to use in login failures
     * 
     * @param \SimpleXMLElement $xml Pointer to <security> tag.
     */
    private function setLoginCallback($xml)
    {
        $loginPage = (string) $xml->authentication->oauth2["login"];
        if (!$loginPage) {
            $loginPage = self::DEFAULT_LOGIN_PAGE;
        }
        $this->loginCallback = $loginPage;
    }
    
    /**
     * Sets callback URL to use in user logout
     * 
     * @param \SimpleXMLElement $xml Pointer to <security> tag.
     */
    private function setLogoutCallback($xml)
    {
        $logoutPage = (string) $xml->authentication->oauth2["logout"];
        if (!$logoutPage) {
            $logoutPage = self::DEFAULT_LOGOUT_PAGE;
        }
        $this->logoutCallback = $logoutPage;
    }
    
    /**
     * Sets callback URL to use in login successes 
     * 
     * @param \SimpleXMLElement $xml Pointer to <security> tag.
     */
    private function setTargetCallback($xml)
    {
        $targetPage = (string) $xml->authentication->oauth2["target"];
        if (!$targetPage) {
            $targetPage = self::DEFAULT_TARGET_PAGE;
        }
        $this->targetCallback = $targetPage;
    }
    
    /**
     * Gets oauth2 drivers detected
     * 
     * @return OAuth2DriverInformation[string]
     */
    public function getDrivers()
    {
        return $this->drivers;
    }
    
    /**
     * Gets name of DAO class detected
     * 
     * @return string
     */
    public function getDaoClass()
    {
        return $this->daoClass;
    }
    
    /**
     * Gets folder in which DAO class resides
     * 
     * @return string
     */
    public function getDaoPath()
    {
        return $this->daoPath;
    }
    
    /**
     * Gets callback URL to use in login failures
     * 
     * @return string
     */
    public function getLoginCallback()
    {
        return $this->loginCallback;
    }
    
    /**
     * Gets callback URL to use in user logout
     * 
     * @return string
     */
    public function getLogoutCallback()
    {
        return $this->logoutCallback;
    }
    
    /**
     * Gets callback URL to use in login successes
     * 
     * @return string
     */
    public function getTargetCallback()
    {
        return $this->targetCallback;
    }
}
