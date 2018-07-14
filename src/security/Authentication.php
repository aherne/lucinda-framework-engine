<?php
require_once("DAOLocator.php");
require_once("SecurityPacket.php");

/**
 * Performs user authentication based on mechanism chosen by developmer in XML (eg: from database via login form, from an oauth2 provider, etc)
 */
class Authentication {
    private $oauth2Drivers = array();

    /**
     * Runs authentication logic. 
     * 
     * @param SimpleXMLElement $xml XML holding information relevant to authentication (above all via security.authentication tag)
     * @param string $page Route requested by client
     * @param string $contextPath Application context path (default "/") necessary if multiple applications are deployed under same hostname
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws ApplicationException If XML is invalid
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    public function __construct(SimpleXMLElement $xml, $page, $contextPath, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers) {
        $wrapper = $this->getWrapper($xml, $page, $csrfTokenDetector, $persistenceDrivers);
        $this->authenticate($wrapper, $contextPath, $persistenceDrivers);
    }
    
    /**
     * Gets driver that performs authentication from security.authentication XML tag.
     * 
     * @param SimpleXMLElement $xmlRoot XML holding information relevant to authentication (above all via security.authentication tag)
     * @param string $page Route requested by client
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws ApplicationException If XML is invalid
     * @return AuthenticationWrapper
     */
    private function getWrapper(SimpleXMLElement $xmlRoot, $page, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers) {
        $xml = $xmlRoot->security->authentication;
        if(empty($xml)) {
            throw new ApplicationException("Entry missing in configuration.xml: security.authentication");
        }
        
        $wrapper = null;
        if($xml->form) {
            if((string) $xml->form["dao"]) {
                require_once("authentication/DAOAuthenticationWrapper.php");
                $wrapper = new DAOAuthenticationWrapper(
                    $xmlRoot,
                    $page,
                    $persistenceDrivers,
                    $csrfTokenDetector);
            } else {
                require_once("authentication/XMLAuthenticationWrapper.php");
                $wrapper = new XMLAuthenticationWrapper(
                    $xmlRoot,
                    $page,
                    $persistenceDrivers,
                    $csrfTokenDetector);
            }
        }
        if($xml->oauth2) {
            require_once("authentication/Oauth2AuthenticationWrapper.php");
            $wrapper = new Oauth2AuthenticationWrapper(
                $xmlRoot,
                $page,
                $persistenceDrivers,
                $csrfTokenDetector);
            // saves oauth2 drivers to be used later on
            $this->oauth2Drivers = $wrapper->getDrivers();
        }
        if(!$wrapper) throw new ApplicationException("No authentication method chosen!");
        return $wrapper;
    }
    
    /**
     * Calls authentication driver detected to perform user authentication.
     * 
     * @param AuthenticationWrapper $wrapper Driver that performs authentication (eg: via form & database).
     * @param string $contextPath Application context path (default "/") necessary if multiple applications are deployed under same hostname
     * @param PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    private function authenticate(AuthenticationWrapper $wrapper, $contextPath, $persistenceDrivers) {
        if(!$wrapper->getResult()) {
            // no authentication was requested
            return;
        } else {
            // authentication was requested
            $transport = new SecurityPacket();
            $transport->setCallback($wrapper->getResult()->getStatus()==AuthenticationResultStatus::DEFERRED?$wrapper->getResult()->getCallbackURI():$contextPath."/".$wrapper->getResult()->getCallbackURI());
            $transport->setStatus($wrapper->getResult()->getStatus());
            $transport->setAccessToken($wrapper->getResult()->getUserID(), $persistenceDrivers);
            throw $transport;
        }
    }

    /*
     * Gets oauth2 drivers found (if authentication method was "oauth2")
     * 
	 * @return array[string:OAuth2\Driver] List of available oauth2 drivers by driver name.
     */
    public function getOAuth2Drivers() {
        return $this->oauth2Drivers;
    }
}