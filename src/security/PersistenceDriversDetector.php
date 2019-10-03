<?php
namespace Lucinda\Framework;

/**
 * Detects mechanisms for authenticated state persistence set in security.persistence XML tag.
 */
class PersistenceDriversDetector
{
    private $persistenceDrivers;
    
    /**
     * Sets persistence drivers based on contents of <persistence> XML tag.
     *
     * @param \SimpleXMLElement $xml XML that contains security.persistence tag.
     * @param string $ipAddress Detected client IP address
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct(\SimpleXMLElement $xml, $ipAddress)
    {
        $this->setPersistenceDrivers($xml, $ipAddress);
    }
    
    /**
     * Detects persistence drivers based on XML
     *
     * @param \SimpleXMLElement $xml XML that contains security.persistence tag.
     * @param string $ipAddress Detected client IP address
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    private function setPersistenceDrivers(\SimpleXMLElement $xml, $ipAddress)
    {
        $xml = $xml->persistence;
        if (empty($xml)) {
            return;
        } // it is allowed for elements to not persist
        
        if ($xml->session) {
            require_once("persistence_drivers/SessionPersistenceDriverWrapper.php");
            $wrapper = new SessionPersistenceDriverWrapper($xml->session, $ipAddress);
            $this->persistenceDrivers[] = $wrapper->getDriver();
        }
        
        if ($xml->remember_me) {
            require_once("persistence_drivers/RememberMePersistenceDriverWrapper.php");
            $wrapper = new RememberMePersistenceDriverWrapper($xml->remember_me, $ipAddress);
            $this->persistenceDrivers[] = $wrapper->getDriver();
        }
        
        if ($xml->synchronizer_token) {
            require_once("persistence_drivers/SynchronizerTokenPersistenceDriverWrapper.php");
            $wrapper = new SynchronizerTokenPersistenceDriverWrapper($xml->synchronizer_token, $ipAddress);
            $this->persistenceDrivers[] = $wrapper->getDriver();
        }
        
        if ($xml->json_web_token) {
            require_once("persistence_drivers/JsonWebTokenPersistenceDriverWrapper.php");
            $wrapper = new JsonWebTokenPersistenceDriverWrapper($xml->json_web_token, $ipAddress);
            $this->persistenceDrivers[] = $wrapper->getDriver();
        }
    }
    
    /**
     * Gets detected drivers for authenticated state persistence.
     *
     * @return \Lucinda\WebSecurity\PersistenceDriver[]
     */
    public function getPersistenceDrivers()
    {
        return $this->persistenceDrivers;
    }
}
