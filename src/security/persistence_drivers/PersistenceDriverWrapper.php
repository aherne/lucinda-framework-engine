<?php
namespace Lucinda\Framework;

/**
 * Defines an abstract persistence mechanism that works with PersistenceDriver objects.
 */
abstract class PersistenceDriverWrapper
{
    protected $driver;
    
    /**
     * Creates an object.
     *
     * @param \SimpleXMLElement $xml Contents of XML tag that sets up persistence driver.
     * @param string $ipAddress Client ip address resolved from headers
     */
    public function __construct(\SimpleXMLElement $xml, $ipAddress)
    {
        $this->setDriver($xml, $ipAddress);
    }
    
    /**
     * Sets up current persistence driver from XML into driver property.
     *
     * @param \SimpleXMLElement $xml Contents of XML tag that sets up persistence driver.
     * @param string $ipAddress Detected client IP address
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    abstract protected function setDriver(\SimpleXMLElement $xml, $ipAddress);
    
    /**
     * Gets current persistence driver.
     *
     * @return \Lucinda\WebSecurity\PersistenceDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }
}
