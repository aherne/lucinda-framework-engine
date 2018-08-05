<?php
require_once("NoSQLDataSourceDetection.php");

class NoSQLDataSourceBinder
{
    public function __construct(Application $application) {
        $environment = $application->getAttribute("environment");
        $xml = $application->getXML()->servers->sql->$environment;
        if(!empty($xml)) {
            if(!$xml->server) throw new ApplicationException("Server not set for environment!");
            $xml = (array) $xml;
            if(is_array($xml["server"])) {
                foreach($xml["server"] as $element) {
                    if(!isset($element["name"])) throw new ApplicationException("Attribute 'name' not set for <server> tag!");
                    $dsd = new NoSQLDataSourceDetection($element);
                    NoSQLConnectionFactory::setDataSource((string) $element["name"], $dsd->getDataSource());
                }
            } else {
                $dsd = new NoSQLDataSourceDetection($xml["server"]);
                NoSQLConnectionSingleton::setDataSource($dsd->getDataSource());
            }
        }
    }
}

