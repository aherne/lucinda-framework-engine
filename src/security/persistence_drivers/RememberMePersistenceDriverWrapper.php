<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/security/src/persistence_drivers/RememberMePersistenceDriver.php");
require_once("PersistenceDriverWrapper.php");
require_once("IPDetector.php");

/**
 * Binds RememberMePersistenceDriver @ SECURITY API with settings from configuration.xml @ SERVLETS-API and sets up an object on which one can
 * forward remember-me cookie operations.
 */
class RememberMePersistenceDriverWrapper extends PersistenceDriverWrapper {
	const DEFAULT_PARAMETER_NAME = "uid";
	const DEFAULT_EXPIRATION_TIME = 24*3600;

	/**
	 * {@inheritDoc}
	 * @see PersistenceDriverWrapper::setDriver()
	 */
	protected function setDriver(\SimpleXMLElement $xml) {
		$secret = (string) $xml["secret"];
		if(!$secret) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'secret' is mandatory for 'remember_me' tag");

		$parameterName = (string) $xml["parameter_name"];
		if(!$parameterName) $parameterName = self::DEFAULT_PARAMETER_NAME;

		$expirationTime = (integer) $xml["expiration"];
		if(!$expirationTime) $expirationTime = self::DEFAULT_EXPIRATION_TIME;

		$isHttpOnly = (integer) $xml["is_http_only"];
		$isHttpsOnly = (integer) $xml["is_https_only"];
		
		$ipDetector = new IPDetector();
		$ipAddress = $ipDetector->getIP();
		
		$this->driver = new \Lucinda\WebSecurity\RememberMePersistenceDriver($secret, $parameterName,$expirationTime,$isHttpOnly,$isHttpsOnly, $ipAddress);
	}
}