<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/logging/loader.php");
require_once("LoggingWrapper.php");
require_once("MultiLogger.php");

/**
 * Binds Logging API with MVC STDOUT API (aka Servlets API) in order to be able to log a message later on (eg: in a file or syslog)
 */
class LoggingBinder
{
    private $logger;
    
    /**
     * Performs loggers detection based on XML and execution environment then aggregates results into a MultiLogger for cascading logs
     *
     * @param \SimpleXMLElement $xml
     * @param string $developmentEnvironment
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct(\SimpleXMLElement $xml, $developmentEnvironment)
    {
        // finds loggers and return a global wrapper
        $finder = new LoggingWrapper($xml, $developmentEnvironment);
        $loggers = $finder->getLoggers();
        if (!empty($loggers)) {
            $this->logger = new MultiLogger($loggers);
        }
    }
    
    /**
     * Gets detected logger
     *
     * @return MultiLogger Allows you to log message to multiple providers at once
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
