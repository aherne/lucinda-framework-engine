<?php
/**
 * Locates and instances loggers based on XML content.
 */
class LoggingWrapper {
	private $loggers = array();
	
	/**
	 * Reads XML tag loggers.{environment}, finds and saves loggers found.
	 *
	 * @param SimpleXMLElement $xml XML tag reference object.
	 */
	public function __construct(SimpleXMLElement $xml) {
		$this->setLoggers($xml);
	}
	
	/**
	 * Reads XML tag for loggers and saves them for later use.
	 * 
	 * @param SimpleXMLElement $xml
	 */
	private function setLoggers(SimpleXMLElement $xml) {
	    $customFolder = (string) $xml["custom_path"];
	    $xmlLoggers = (array) $xml;
	    foreach($xmlLoggers["logger"] as $xmlProperties) {
	        $className = (string) $xmlProperties["class"];
            $loggerWrapper = null;
	        switch($className) {
	            case "SysLoggerWrapper":
                case "FileLoggerWrapper":
                    require_once("loggers/".$className.".php");
                    $loggerWrapper = new $className($xmlProperties);
	                break;
	            default:
	                if(!$customFolder) {
	                    throw new ApplicationException("Attribute not set in XML loggers.(environment).logger: folder!");
                    }
                    if(!file_exists($customFolder."/".$className.".php")) {
                        throw new ServletException("Custom logger not found!");
                    }
                    require_once($customFolder."/".$className.".php");
	                if(!class_exists($className)) {
	                    throw new ServletException("Logger class not found!");
                    }
                    $loggerWrapper = new $className($xmlProperties);
	                if(!$loggerWrapper instanceof AbstractLoggerWrapper) {
	                    throw new ServletException("Logger must be instance of AbstractLoggerWrapper!");
                    }
	                break;
	        }
            $this->loggers[] = $loggerWrapper->getLogger();
	    }
	}
	
	/**
	 * Gets detected logger.
	 *
	 * @return Logger[] List of loggers found.
	 */
	public function getLoggers() {
		return $this->loggers;
	}
}