<?php
namespace Lucinda\Framework;

/**
 * Encapsulates information about oauth2 provider detected from a &lt;driver&gt; tag
 */
class OAuth2DriverInformation
{
    private $driverName;
    private $clientId;
    private $clientSecret;
    private $callbackUrl;
    private $applicationName;
    private $scopes = array();
    
    /**
     * Starts detection process
     * 
     * @param \SimpleXMLElement $element Pointer to tag content
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    public function __construct(\SimpleXMLElement $element)
    {
        $this->driverName = (string) $element["name"];
        $this->clientId = (string) $element["client_id"];
        $this->clientSecret = (string) $element["client_secret"];
        $this->callbackUrl = (string) $element["callback"];
        if (!$this->driverName || !$this->clientId || !$this->clientSecret || !$this->callbackUrl) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Attributes are mandatory for 'driver' @ 'oauth2' tag: name, client_id, client_secret, callback");
        }
        if ($this->driverName == "GitHub") {
            $this->applicationName = (string) $element["application_name"];
            if (!$this->applicationName) {
                throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'application_name' is mandatory for GitHub 'driver' @ 'oauth2' tag!");
            }
        }
        $this->scopes = explode(",", (string) $element["scopes"]);
    }
    
    /**
     * Gets name of vendor
     * 
     * @return string
     */
    public function getDriverName()
    {
        return $this->driverName;
    }
    
    /**
     * Gets client id to send to vendor in order to obtain an authorization code
     * 
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }    
    
    /**
     * Gets client secret to use in converting authorization code to access token 
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }
    
    /**
     * Gets relative url vendor will use to send authorization code
     * 
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }
    
    /**
     * Gets application name (requirement when GitHub is used)
     * 
     * @return string
     */
    public function getApplicationName()
    {
        return $this->applicationName;
    }
    
    /**
     * Gets scopes to send to vendor in order on authorization code requests
     * 
     * @return string[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }
}