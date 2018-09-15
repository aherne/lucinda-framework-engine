<?php
require_once("MyLogger.php");

class MyLoggerWrapper extends Lucinda\Framework\AbstractLoggerWrapper {
    protected function setLogger(SimpleXMLElement $xml)
    {
        $this->logger = new MyLogger();
    }   
}