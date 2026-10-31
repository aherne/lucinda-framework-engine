<?php

namespace Lucinda\Framework;

use Lucinda\SQL\ConfigurationException;
use Lucinda\SQL\Connection;
use Lucinda\SQL\DataSource;
use Lucinda\SQL\Exception;

/**
 * Provides lazy SQL connections from environment-specific XML
 *
 * Configuration is read from `sql > {environment}`. A single unnamed server is
 * addressed with the default empty name; multiple servers must have names.
 * Connections are opened on first access, cached for reuse, and disconnected
 * when this provider is destroyed.
 */
final class SqlConnectionProvider
{
    /**
     * @var array<string,DataSource> Configured data sources keyed by server name
     */
    private array $dataSources = [];

    /**
     * @var array<string,Connection> Open connections keyed by server name
     */
    private array $connections = [];

    /**
     * Loads SQL data sources for the selected environment
     *
     * An environment without an `sql` configuration is accepted and leaves the
     * provider empty.
     *
     * @param \SimpleXMLElement $xml Application XML containing the `sql` section
     * @throws ConfigurationException If the environment has no server or contains an invalid server definition
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $xml = $xml->sql;
        if (empty($xml)) {
            return;
        }
        if (!$xml->server) {
            throw new ConfigurationException("Server not set for environment!");
        }
        $this->dataSources = $this->getDataSources($xml);
    }

    /**
     * Gets or opens the connection configured under a server name
     *
     * Successfully opened connections are cached and reused for subsequent
     * requests with the same name.
     *
     * @param string $serverName Configured server name, or an empty string for a single unnamed server
     * @return Connection Cached or newly opened SQL connection
     * @throws Exception If no data source exists under the requested name
     * @throws \Lucinda\SQL\ConnectionException If the connection cannot be established
     */
    public function getConnection(string $serverName = ""): Connection
    {
        if (!isset($this->connections[$serverName])) {
            if (!isset($this->dataSources[$serverName])) {
                throw new Exception("Datasource not set for: ".$serverName);
            }
            $connection = new Connection();
            $connection->connect($this->dataSources[$serverName]);
            $this->connections[$serverName] = $connection;
        }
        return $this->connections[$serverName];
    }

    /**
     * Builds the data-source map from configured server elements
     *
     * A collection of server elements is keyed by each required `name`
     * attribute. A single server uses the empty-string key.
     *
     * @param \SimpleXMLElement $xml Selected environment's `sql` element
     * @return array<string,DataSource> Data sources keyed by their lookup names
     * @throws ConfigurationException If a named server has no name or a server definition is invalid
     */
    private function getDataSources(\SimpleXMLElement $xml): array
    {
        $output = [];
        $xml = (array) $xml;
        if (is_array($xml["server"])) {
            foreach ($xml["server"] as $element) {
                $name = (string) $element["name"];
                if (!$name) {
                    throw new ConfigurationException("Attribute 'name' is mandatory for 'server' tag");
                }
                if (isset($output[$name])) {
                    throw new ConfigurationException("A 'server' tag with the same 'name' attribute already exists");
                }
                $output[$name] = new DataSource($element);
            }
        } else {
            $output[""] = new DataSource($xml["server"]);
        }
        return $output;
    }

    /**
     * Disconnects every connection opened by this provider
     *
     * Cleanup exceptions are suppressed so object destruction cannot interrupt
     * application shutdown.
     */
    public function __destruct()
    {
        foreach ($this->connections as $connection) {
            try {
                $connection->disconnect();
            } catch (\Exception) {
            }
        }
    }
}
