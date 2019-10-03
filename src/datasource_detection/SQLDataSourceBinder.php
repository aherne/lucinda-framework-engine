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
     * Binds SQL Data Access API to XML based on development environment and sets DataSource for later querying
     *
     * @param \SimpleXMLElement $xml
     * @param string $developmentEnvironment
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct(\SimpleXMLElement $xml, $developmentEnvironment)
    {
        $xml = $xml->$developmentEnvironment;
        if (!empty($xml)) {
            if (!$xml->server) {
                throw new \Lucinda\MVC\STDOUT\XMLException("Server not set for environment!");
            }
            $xml = (array) $xml;
            if (is_array($xml["server"])) {
                foreach ($xml["server"] as $element) {
                    if (!isset($element["name"])) {
                        throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'name' is mandatory for 'server' tag");
                    }
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
