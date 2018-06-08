<?php
require_once("MyLogger.php");

class MyLoggerWrapper extends AbstractLoggerWrapper {
    protected function setLogger(SimpleXMLElement $xml)
    {
        $this->logger = new MyLogger();
    }   
}