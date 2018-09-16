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
     * @param \Lucinda\MVC\STDOUT\Application $application
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application) {        
        // finds loggers and return a global wrapper
        $finder = new LoggingWrapper($application->getTag("loggers"), $application->attributes()->get("environment"));
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

