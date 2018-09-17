<?php
namespace Lucinda\Framework;
require_once("AbstractLoggerWrapper.php");
require_once(dirname(__DIR__)."/ClassLoader.php");
/**
 * Locates and instances loggers based on XML content.
 */
class LoggingWrapper {
	private $loggers = array();
	
	/**
	 * Reads XML tag loggers.{environment}, finds and saves loggers found.
	 *
     * @param \SimpleXMLElement $xml XML containing logger settings.
     * @param string $developmentEnvironment Development environment server is running into (eg: local, dev, live)
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid.
     * @throws  \Lucinda\MVC\STDOUT\ServletException If pointed file doesn't exist or is invalid
	 */
	public function __construct(\SimpleXMLElement $xml, $developmentEnvironment) {
	    $loggersPath = (string) $xml["path"];
        if(!$loggersPath) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'path' is mandatory for 'loggers' tag");
		$this->setLoggers($loggersPath, $xml->{$developmentEnvironment});
	}
	
	/**
	 * Reads XML tag for loggers and saves them for later use.
	 *
     * @param string $loggersPath Path to logger classes.
	 * @param \SimpleXMLElement $xml XML containing individual logger settings.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid.
     * @throws  \Lucinda\MVC\STDOUT\ServletException If pointed file doesn't exist or is invalid
	 */
	private function setLoggers($loggersPath, \SimpleXMLElement $xml) {
	    $xmlLoggers = (array) $xml;
	    if(empty($xmlLoggers["logger"])) {
	        return;
	    } else if(!is_array($xmlLoggers["logger"])){
	        $xmlLoggers["logger"] = array(0=>$xmlLoggers["logger"]);
	    }
	    foreach($xmlLoggers["logger"] as $xmlProperties) {
	        // detects class name
            $className = (string) $xmlProperties["class"];

            // loads class
            load_class($loggersPath, $className);

            // creates and checks object
            $loggerWrapper = new $className($xmlProperties);
            if(!$loggerWrapper instanceof AbstractLoggerWrapper) {
                throw new  \Lucinda\MVC\STDOUT\ServletException("Logger must be instance of AbstractLoggerWrapper!");
            }

            // sets object
            $this->loggers[] = $loggerWrapper->getLogger();
	    }
	}
	
	/**
	 * Gets detected logger.
	 *
	 * @return \Lucinda\Logging\Logger[] List of loggers found.
	 */
	public function getLoggers() {
		return $this->loggers;
	}
}