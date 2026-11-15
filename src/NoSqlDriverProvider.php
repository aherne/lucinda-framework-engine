<?php

namespace Lucinda\Framework;

use Lucinda\Framework\NoSQL\APCuAtomicDriver;
use Lucinda\Framework\NoSQL\AtomicDriver;
use Lucinda\Framework\NoSQL\MemcachedAtomicDriver;
use Lucinda\Framework\NoSQL\RedisAtomicDriver;
use Lucinda\NoSQL\ConfigurationException;
use Lucinda\NoSQL\ConnectionException;
use Lucinda\NoSQL\DataSource;
use Lucinda\NoSQL\Driver;
use Lucinda\NoSQL\Server;
use Lucinda\NoSQL\Vendor\APCu\DataSource as APCuDataSource;
use Lucinda\NoSQL\Vendor\Memcached\DataSource as MemcachedDataSource;
use Lucinda\NoSQL\Vendor\Redis\DataSource as RedisDataSource;
use Lucinda\NoSQL\Vendor\Valkey\DataSource as ValkeyDataSource;
use Lucinda\NoSQL\Vendor\APCu\Driver as APCuDriver;
use Lucinda\NoSQL\Vendor\Memcached\Driver as MemcachedDriver;
use Lucinda\NoSQL\Vendor\Redis\Driver as RedisDriver;

/**
 * Provides lazy ordinary drivers and cached atomic framework adapters
 *
 * Configuration is read directly from nosql/server. A single server
 * is addressed with the default empty name; multiple servers must have names.
 * Drivers are created on first access, cached for reuse, and disconnected when
 * this provider is destroyed when they use a server connection.
 */
final class NoSqlDriverProvider
{
    /**
     * @var array<string,DataSource> Data sources keyed by server lookup name
     */
    private array $dataSources = [];

    /**
     * @var array<string,Driver> Initialized drivers keyed by server name
     */
    private array $drivers = [];

    /** @var array<string,AtomicDriver> Atomic adapters keyed by server name */
    private array $atomicDrivers = [];

    /**
     * Loads NoSQL data sources mapped by the application's listener
     *
     * Missing nosql configuration is accepted and leaves the provider empty.
     *
     * @param \SimpleXMLElement $xml Application XML with a nosql section
     * @throws ConfigurationException If server definitions are invalid
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
     * @param string $serverName Lookup name, empty for a single server
     * @return Driver Cached or newly initialized NoSQL driver
     * @throws ConnectionException If lookup or connection setup fails
     * @throws ConfigurationException If the driver cannot be configured
     */
    public function getDriver(string $serverName = ""): Driver
    {
        if (!isset($this->drivers[$serverName])) {
            if (!isset($this->dataSources[$serverName])) {
                throw new ConnectionException(
                    'Datasource not set for: ' . $serverName
                );
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
     * Gets a cached atomic adapter without changing the ordinary driver API
     *
     * Valkey inherits the Redis protocol driver and uses the same adapter.
     * The provider retains both the original driver and its atomic wrapper.
     *
     * @param string $serverName Lookup name, empty for a single server
     * @return AtomicDriver Adapter sharing the configured native connection
     * @throws ConfigurationException If the driver has no atomic adapter
     * @throws \Throwable If driver initialization fails
     */
    public function getAtomicDriver(string $serverName = ''): AtomicDriver
    {
        if (!isset($this->atomicDrivers[$serverName])) {
            $driver = $this->getDriver($serverName);
            if ($driver instanceof RedisDriver) {
                $adapter = new RedisAtomicDriver($driver);
            } elseif ($driver instanceof MemcachedDriver) {
                $adapter = new MemcachedAtomicDriver($driver);
            } elseif ($driver instanceof APCuDriver) {
                $adapter = new APCuAtomicDriver($driver);
            } else {
                throw new ConfigurationException(
                    'No atomic adapter for driver'
                );
            }
            $this->atomicDrivers[$serverName] = $adapter;
        }
        return $this->atomicDrivers[$serverName];
    }

    /**
     * Builds the data-source map from configured server elements
     *
     * A collection of server elements is keyed by each required `name`
     * attribute. A single server uses the empty-string key.
     *
     * @param \SimpleXMLElement $xml Selected environment's `nosql` element
     * @return array<string,DataSource> Data sources keyed by their lookup names
     * @throws ConfigurationException If names or definitions are invalid
     */
    private function getDataSources(\SimpleXMLElement $xml): array
    {
        $output = [];
        $xml = (array) $xml;
        if (is_array($xml["server"])) {
            foreach ($xml["server"] as $element) {
                if (!isset($element["name"])) {
                    throw new ConfigurationException(
                        "Attribute 'name' is mandatory for 'server' tag"
                    );
                }
                $name = (string) $element["name"];
                if (isset($output[$name])) {
                    throw new ConfigurationException(
                        "A server with the same name already exists"
                    );
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
     * Supported identifiers are memcached, redis, valkey and apcu.
     *
     * @param \SimpleXMLElement $databaseInfo Configured `server` element
     * @return DataSource Data source matching the declared driver
     * @throws ConfigurationException If the driver or settings are invalid
     */
    private function getDataSource(\SimpleXMLElement $databaseInfo): DataSource
    {
        $driver = (string) $databaseInfo["driver"];
        if (!$driver) {
            throw new ConfigurationException(
                "Attribute 'driver' is mandatory for server tags"
            );
        }
        return match ($driver) {
            "memcached" => new MemcachedDataSource($databaseInfo),
            "redis" => new RedisDataSource($databaseInfo),
            "valkey" => new ValkeyDataSource($databaseInfo),
            "apcu" => new APCuDataSource(),
            default => throw new ConfigurationException(
                'NoSQL driver not supported: ' . $driver
            )
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
