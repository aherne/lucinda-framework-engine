<?php
require_once("SQLDataSourceDetection.php");

/**
 * Binds SQL Data Access API with MVC STDOUT API (aka Servlets API) in order to detect a DataSource that will be automatically used later on when SQL server is queried
 */
class SQLDataSourceBinder
{
    /**
     * @param Application $application
     * @throws ApplicationException If XML is invalid.
     */
    public function __construct(Application $application) {        
        $environment = $application->getAttribute("environment");
        $xml = $application->getXML()->servers->sql->$environment;
        if(!empty($xml)) {
            if(!$xml->server) throw new ApplicationException("Server not set for environment!");
            $xml = (array) $xml;
            if(is_array($xml["server"])) {
                foreach($xml["server"] as $element) {
                    if(!isset($element["name"])) throw new ApplicationException("Attribute 'name' not set for <server> tag!");
                    $dsd = new SQLDataSourceDetection($element);
                    SQLConnectionFactory::setDataSource((string) $element["name"], $dsd->getDataSource());
                }
            } else {
                $dsd = new SQLDataSourceDetection($xml["server"]);
                SQLConnectionSingleton::setDataSource($dsd->getDataSource());
            }
        }
    }
}