<?php

namespace Lucinda\Framework;

use Lucinda\NoSQL\ConfigurationException;
use Lucinda\NoSQL\ConnectionException;
use Lucinda\NoSQL\DataSource;
use Lucinda\NoSQL\Driver;
use Lucinda\NoSQL\Server;
use Lucinda\NoSQL\Vendor\APCu\DataSource as APCuDataSource;
use Lucinda\NoSQL\Vendor\Memcached\DataSource as MemcachedDataSource;
use Lucinda\NoSQL\Vendor\Redis\DataSource as RedisDataSource;

/**
 * Provides NoSQL drivers to query based on XML configuration.
 */
final class NoSqlDriverProvider
{
    /**
     * @var array<string,DataSource>
     */
    private array $dataSources = [];

    /**
     * @var array<string,Driver>
     */
    private array $drivers = [];

    /**
     * Bootstraps the process
     * 
     * @param \SimpleXMLElement $xml
     * @param string            $environment
     * @throws ConfigurationException
     */
    public function __construct(\SimpleXMLElement $xml, string $environment)
    {
        $xml = $xml->nosql->{$environment};
        if (empty($xml)) {
            return;
        }
        if (!$xml->server) {
            throw new ConfigurationException("Server not set for environment!");
        }
        $this->dataSources = $this->getDataSources($xml);
    }

    /**
     * Gets driver by server name
     * 
     * @param string $serverName
     * @return Driver
     * @throws ConnectionException
     * @throws ConfigurationException
     */
    public function getDriver(string $serverName = ""): Driver
    {
        if (!isset($this->drivers[$serverName])) {
            if (!isset($this->dataSources[$serverName])) {
                throw new ConnectionException("Datasource not set for: ".$serverName);
            }
            $driver = $this->dataSources[$serverName]->getDriver();
            if ($driver instanceof Server) {
                $driver->connect($this->dataSources[$serverName]);
            }
            $this->drivers[$serverName] = $driver;
        }
        return $this->drivers[$serverName];
    }

    /**
     * Detects data sources from XML
     * 
     * @param \SimpleXMLElement $xml
     * @return array<string,DataSource>
     * @throws ConfigurationException
     */
    private function getDataSources(\SimpleXMLElement $xml): array
    {
        $output = [];
        $xml = (array) $xml;
        if (is_array($xml["server"])) {
            foreach ($xml["server"] as $element) {
                if (!isset($element["name"])) {
                    throw new ConfigurationException("Attribute 'name' is mandatory for 'server' tag");
                }
                $output[(string) $element["name"]] = $this->getDataSource($element);
            }
        } else {
            $output[""] = $this->getDataSource($xml["server"]);
        }
        return $output;
    }

    /**
     * Gets data sources detected
     * 
     * @param \SimpleXMLElement $databaseInfo
     * @return DataSource
     * @throws ConfigurationException
     */
    private function getDataSource(\SimpleXMLElement $databaseInfo): DataSource
    {
        $driver = (string) $databaseInfo["driver"];
        if (!$driver) {
            throw new ConfigurationException("Child tag 'driver' is mandatory for 'server' tags");
        }
        return match ($driver) {
            "memcached" => new MemcachedDataSource($databaseInfo),
            "redis" => new RedisDataSource($databaseInfo),
            "apcu" => new APCuDataSource(),
            default => throw new ConfigurationException("NoSQL driver not supported: " . $driver)
        };
    }

    /**
     * Closes connection to target servers
     */
    public function __destruct()
    {
        foreach ($this->drivers as $driver) {
            try {
                if ($driver instanceof Server) {
                    $driver->disconnect();
                }
            } catch (\Exception) {
            }
        }
    }
}
