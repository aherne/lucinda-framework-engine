<?php
require_once("DAOLocator.php");
require_once("SecurityPacket.php");

class Authorization {
    public function __construct(SimpleXMLElement $xml, $page, $contextPath, $userID) {
        $wrapper = $this->getWrapper($xml, $page, $userID);
        $this->authorize($wrapper, $contextPath);
    }
    
    private function getWrapper(SimpleXMLElement $xmlRoot, $page, $userID) {
        $xml = $xmlRoot->security->authorization;
        if(empty($xml)) {
            throw new ApplicationException("Entry missing in configuration.xml: security.authentication");
        }
        
        $wrapper = null;
        if($xml->by_route) {
            require_once("authorization/XMLAuthorizationWrapper.php");
            $wrapper = new XMLAuthorizationWrapper(
                $xmlRoot,
                $page,
                $userID);
        }
        if($xml->by_dao) {
            require_once("authorization/DAOAuthorizationWrapper.php");
            $wrapper = new DAOAuthorizationWrapper(
                $xmlRoot,
                $page,
                $userID);
        }
        return $wrapper;
    }
    
    private function authorize(AuthorizationWrapper $wrapper, $contextPath) {
        if($wrapper) {
            if($wrapper->getResult()->getStatus() == AuthorizationResultStatus::OK) {
                // authorization was successful
                return;
            } else {
                // authorization failed
                $transport = new SecurityPacket();
                $transport->setCallback($contextPath."/".$wrapper->getResult()->getCallbackURI());
                $transport->setStatus($wrapper->getResult()->getStatus());
                throw $transport;
            }
        } else {
            throw new ApplicationException("No authorization driver found in configuration.xml: security.authentication");
        }
    }
}