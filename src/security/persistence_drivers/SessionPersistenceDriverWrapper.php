<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/security/src/persistence_drivers/SessionPersistenceDriver.php");
require_once("PersistenceDriverWrapper.php");

/**
 * Binds SessionPersistenceDriver @ SECURITY API with settings from configuration.xml @ SERVLETS-API and sets up an object on which one can
 * forward session persistence operations.
 */
class SessionPersistenceDriverWrapper extends PersistenceDriverWrapper
{
    const DEFAULT_PARAMETER_NAME = "uid";
    const HANDLER_FOLDER = "application/models";

    /**
     * {@inheritDoc}
     * @see PersistenceDriverWrapper::setDriver()
     */
    protected function setDriver(\SimpleXMLElement $xml, $ipAddress)
    {
        $parameterName = (string) $xml["parameter_name"];
        if (!$parameterName) {
            $parameterName = self::DEFAULT_PARAMETER_NAME;
        }

        $expirationTime = (integer) $xml["expiration"];
        $isHttpOnly = (integer) $xml["is_http_only"];
        $isHttpsOnly = (integer) $xml["is_https_only"];
        
        $handler = (string) $xml["handler"];
        if ($handler) {
            session_set_save_handler($this->getHandlerInstance($handler), true);
        }
        
        $this->driver = new \Lucinda\WebSecurity\SessionPersistenceDriver($parameterName, $expirationTime, $isHttpOnly, $isHttpsOnly, $ipAddress);
    }
    
    /**
     * Gets instance of handler based on handler name
     *
     * @param string $handlerName Name of handler class
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @return \SessionHandlerInterface
     */
    private function getHandlerInstance($handlerName)
    {
        $file = self::HANDLER_FOLDER."/".$handlerName.".php";
        if (!file_exists($file)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Handler file not found: ".$file);
        }
        require_once($file);
        if (!class_exists($handlerName)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Handler class not found: ".$handlerName);
        }
        $object = new $handlerName();
        if (!($object instanceof \SessionHandlerInterface)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Handler must be instance of SessionHandlerInterface!");
        }
        return $object;
    }
}
