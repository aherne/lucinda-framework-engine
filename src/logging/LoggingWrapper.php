<?php
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
     * @param SimpleXMLElement $xml XML containing logger settings.
     * @param string $developmentEnvironment Development environment server is running into (eg: local, dev, live)
     * @throws ApplicationException If XML is invalid.
     * @throws ServletException If pointed file doesn't exist or is invalid
	 */
	public function __construct(SimpleXMLElement $xml, $developmentEnvironment) {
	    $loggersPath = (string) $xml->application->paths->loggers;
        if(!$loggersPath) throw new ApplicationException("Entry missing in configuration.xml: application.paths.loggers");
		$this->setLoggers($loggersPath, $xml->loggers->{$developmentEnvironment});
	}
	
	/**
	 * Reads XML tag for loggers and saves them for later use.
	 *
     * @param string $loggersPath Path to logger classes.
	 * @param SimpleXMLElement $xml XML containing individual logger settings.
     * @throws ApplicationException If XML is invalid.
     * @throws ServletException If pointed file doesn't exist or is invalid
	 */
	private function setLoggers($loggersPath, SimpleXMLElement $xml) {
	    $xmlLoggers = (array) $xml;
	    foreach($xmlLoggers["logger"] as $xmlProperties) {
	        // detects class name
            $className = (string) $xmlProperties["class"];

            // loads class
            load_class($loggersPath, $className);

            // creates and checks object
            $loggerWrapper = new $className($xmlProperties);
            if(!$loggerWrapper instanceof AbstractLoggerWrapper) {
                throw new ServletException("Logger must be instance of AbstractLoggerWrapper!");
            }

            // sets object
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