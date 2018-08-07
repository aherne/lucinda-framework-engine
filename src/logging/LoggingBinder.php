<?php
require_once("LoggingWrapper.php");
require_once("MultiLogger.php");

/**
 * Binds Logging API with MVC STDOUT API (aka Servlets API) in order to be able to log a message later on (eg: in a file or syslog)
 */
class LoggingBinder
{
    private $logger;
    
    /**
     * @param Application $application
     */
    public function __construct(Application $application) {
        //validates XML
        $developmentEnvironment = $application->getAttribute("environment");
        $xml = $application->getXML()->loggers;
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
    
    /**
     * Gets detected logger
     * 
     * @return MultiLogger Allows you to log message to multiple providers at once
     */
    public function getLogger() {
        return $this->logger;
    }
}

