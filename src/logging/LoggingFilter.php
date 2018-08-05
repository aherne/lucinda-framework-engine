<?php
require_once("LoggingWrapper.php");
require_once("MultiLogger.php");

class LoggingFilter
{
    private $logger;
    
    public function __construct(SimpleXMLElement $mainXML, $developmentEnvironment) {
        // look for container tag
        $xml = $mainXML->loggers;
        if(empty($xml) || empty($xml->$developmentEnvironment)) {
            return;
        }
        
        // finds loggers and return a global wrapper
        $finder = new LoggingWrapper($xml->$developmentEnvironment);
        $loggers = $finder->getLoggers();
        if(!empty($loggers)) {
            $this->logger = new MultiLogger($loggers);
        }	
    }
    
    public function getLogger() {
        return $this->logger;
    }
}

