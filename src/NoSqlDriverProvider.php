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
 * Provides lazily connected NoSQL drivers from environment-specific XML
 *
 * Configuration is read from `nosql > {environment}`. A single unnamed server
 * is addressed with the default empty name; multiple servers must have names.
 * Drivers are created on first access, cached for reuse, and disconnected when
 * this provider is destroyed when they use a server connection.
 */
final class NoSqlDriverProvider
{
    /**
     * @var array<string,DataSource> Configured data sources keyed by server name
     */
    private array $dataSources = [];

    /**
     * @var array<string,Driver> Initialized drivers keyed by server name
     */
    private array $drivers = [];

    /**
     * Loads NoSQL data sources for the selected environment
     *
     * An environment without a `nosql` configuration is accepted and leaves
     * the provider empty.
     *
     * @param \SimpleXMLElement $xml Application XML containing the `nosql` section
     * @throws ConfigurationException If the environment has no server or contains an invalid server definition
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $xml = $xml->nosql;
        if (empty($xml)) {
            return;
        }
        if (!$xml->server) {
            throw new ConfigurationException("Server not set for environment!");
        }
        $this->dataSources = $this->getDataSources($xml);
    }

    /**
     * Gets or initializes the driver configured under a server name
     *
     * Server-backed drivers are connected before being cached. Local drivers,
     * such as APCu, are returned without a connection step.
     *
     * @param string $serverName Configured server name, or an empty string for a single unnamed server
     * @return Driver Cached or newly initialized NoSQL driver
     * @throws ConnectionException If no data source exists under the requested name or the server cannot connect
     * @throws ConfigurationException If the configured data source cannot create its driver
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
     * Builds the data-source map from configured server elements
     *
     * A collection of server elements is keyed by each required `name`
     * attribute. A single server uses the empty-string key.
     *
     * @param \SimpleXMLElement $xml Selected environment's `nosql` element
     * @return array<string,DataSource> Data sources keyed by their lookup names
     * @throws ConfigurationException If a named server has no name or a server definition is invalid
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
                $name = (string) $element["name"];
                if (isset($output[$name])) {
                    throw new ConfigurationException("A 'server' tag with the same 'name' attribute already exists");
                }
                $output[$name] = $this->getDataSource($element);
            }
        } else {
            $output[""] = $this->getDataSource($xml["server"]);
        }
        return $output;
    }

    /**
     * Creates the vendor-specific data source declared by a server element
     *
     * Supported driver identifiers are `memcached`, `redis`, and `apcu`.
     *
     * @param \SimpleXMLElement $databaseInfo Configured `server` element
     * @return DataSource Data source matching the declared driver
     * @throws ConfigurationException If the driver is missing, unsupported, or configured incorrectly
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
     * Disconnects every initialized server-backed driver
     *
     * Cleanup exceptions are suppressed so object destruction cannot interrupt
     * application shutdown. Local non-server drivers require no cleanup.
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
