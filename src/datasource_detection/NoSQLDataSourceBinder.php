<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/nosql-data-access/loader.php");
require_once("NoSQLDataSourceDetection.php");

/**
 * Binds NoSQL Data Access API with MVC STDOUT API (aka Servlets API) in order to detect a DataSource that will be automatically used later on when NoSQL server is queried
 */
class NoSQLDataSourceBinder
{
    /**
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application) {
        $environment = $application->attributes()->get("environment");
        $xml = $application->getTag("servers")->nosql->$environment;
        if(!empty($xml)) {
            if(!$xml->server) throw new \Lucinda\MVC\STDOUT\XMLException("Server not set for environment!");
            $xml = (array) $xml;
            if(is_array($xml["server"])) {
                foreach($xml["server"] as $element) {
                    if(!isset($element["name"])) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'name' is mandatory for 'server' tag");
                    $dsd = new NoSQLDataSourceDetection($element);
                    \Lucinda\NoSQL\ConnectionFactory::setDataSource((string) $element["name"], $dsd->getDataSource());
                }
            } else {
                $dsd = new NoSQLDataSourceDetection($xml["server"]);
                \Lucinda\NoSQL\ConnectionSingleton::setDataSource($dsd->getDataSource());
            }
        }
    }
}

