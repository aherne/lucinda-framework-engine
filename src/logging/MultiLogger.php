<?php
namespace Lucinda\Framework;
/**
 * Implements a logger that forwards internally to multiple loggers.
 */
class MultiLogger extends \Lucinda\Logging\Logger {
	private $loggers;
	
	/**
	 * Creates an object.
	 * 
	 * @param \Lucinda\Logging\Logger[] $loggers List of loggers to delegate logging to.
	 */
	public function __construct($loggers) {
		$this->loggers = $loggers;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Lucinda\Logging\Logger::log()
	 */
	public function log($info, $level) {
		foreach($this->loggers as $logger) {
			$logger->log($info, $level);
		}
	}
}