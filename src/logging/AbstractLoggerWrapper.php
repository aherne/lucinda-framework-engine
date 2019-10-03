<?php
namespace Lucinda\Framework;

/**
 * Implements an abstract converter from an XML line (child of loggers.{environment}) to a Logger instance @ LoggingAPI
 */
abstract class AbstractLoggerWrapper
{
    protected $logger;
    
    /**
     * Calls children to return a \Lucinda\Logger instance from matching "logger" XML tag
     *
     * @param \SimpleXMLElement $xml XML tag that is child of loggers.(environment)
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->logger = $this->setLogger($xml);
    }
    
    /**
     * Detects Logger instance based on XML tag supplied
     *
     * @param \SimpleXMLElement $xml XML tag that is child of loggers.(environment)
     * @return \Lucinda\Logging\Logger
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    abstract protected function setLogger(\SimpleXMLElement $xml);
    
    /**
     * Gets detected logger
     *
     * @return \Lucinda\Logging\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
