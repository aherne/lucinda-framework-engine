<?php
require_once("PersistenceDriversDetector.php");
require_once("UserIdDetector.php");
require_once("CsrfTokenDetector.php");
require_once("Authentication.php");
require_once("Authorization.php");

class SecurityFilter {
    private $persistenceDrivers = array();
    private $oauth2Drivers = array();
    private $userID;
    private $csrfToken;

    public function __construct(SimpleXMLElement $xml, $page, $contextPath) {
        $this->setPersistenceDrivers($xml);
        $this->setUserID();
        $this->setCsrfToken($xml);
        $this->authenticate($xml, $page, $contextPath);
        $this->authorize($xml, $page, $contextPath);
    }

    private function setPersistenceDrivers($mainXML) {
        $pdd = new PersistenceDriversDetector($mainXML);
        $this->persistenceDrivers = $pdd->getPersistenceDrivers();
    }

    private function setCsrfToken(SimpleXMLElement $mainXML) {
        $this->csrfToken = new CsrfTokenDetector($mainXML);
    }

    private function setUserID() {
        $udd = new UserIdDetector($this->persistenceDrivers);
        $this->userID = $udd->getUserID();
    }

    private function authenticate(SimpleXMLElement $mainXML, $page, $contextPath) {
        $authentication = new Authentication($mainXML, $page, $contextPath, $this->csrfToken, $this->persistenceDrivers);
        $this->oauth2Drivers = $authentication->getOAuth2Drivers();
    }

    private function authorize(SimpleXMLElement $mainXML, $page, $contextPath) {
        new Authorization($mainXML, $page, $contextPath, $this->userID);
    }

    public function getUserID() {
        return $this->userID;
    }

    public function getCsrfToken() {
        return $this->csrfToken;
    }

    public function getOAuth2Drivers() {
        return $this->oauth2Drivers;
    }

    public function getPersistenceDrivers() {
        return $this->persistenceDrivers;
    }
}