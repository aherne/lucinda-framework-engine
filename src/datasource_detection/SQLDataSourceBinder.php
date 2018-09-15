<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/sql-data-access/loader.php");
require_once("SQLDataSourceDetection.php");

/**
 * Binds SQL Data Access API with MVC STDOUT API (aka Servlets API) in order to detect a DataSource that will be automatically used later on when SQL server is queried
 */
class SQLDataSourceBinder
{
    /**
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application) {        
        $environment = $application->attributes()->get("environment");
        $xml = $application->getTag("servers")->sql->$environment;
        if(!empty($xml)) {
            if(!$xml->server) throw new \Lucinda\MVC\STDOUT\XMLException("Server not set for environment!");
            $xml = (array) $xml;
            if(is_array($xml["server"])) {
                foreach($xml["server"] as $element) {
                    if(!isset($element["name"])) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'name' not set for <server> tag!");
                    $dsd = new SQLDataSourceDetection($element);
                    \Lucinda\SQL\ConnectionFactory::setDataSource((string) $element["name"], $dsd->getDataSource());
                }
            } else {
                $dsd = new SQLDataSourceDetection($xml["server"]);
                \Lucinda\SQL\ConnectionSingleton::setDataSource($dsd->getDataSource());
            }
        }
    }
}