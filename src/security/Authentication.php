<?php
require_once("DAOLocator.php");
require_once("SecurityPacket.php");

class Authentication {
    private $oauth2Drivers = array();

    public function __construct(SimpleXMLElement $xml, $page, $contextPath, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers) {
        $wrapper = $this->getWrapper($xml, $page, $csrfTokenDetector, $persistenceDrivers);
        $this->authenticate($wrapper, $contextPath, $persistenceDrivers);
    }
    
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
        return $wrapper;
    }
    
    private function authenticate(AuthenticationWrapper $wrapper, $contextPath, $persistenceDrivers) {
        if($wrapper) {
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
        } else {
            throw new ApplicationException("No authentication driver found in configuration.xml: security.authentication");
        }
    }

    public function getOAuth2Drivers() {
        return $this->oauth2Drivers;
    }
}