<?php

namespace Lucinda\Framework;

use Lucinda\SQL\ConfigurationException;
use Lucinda\SQL\Connection;
use Lucinda\SQL\DataSource;
use Lucinda\SQL\Exception;

/**
 * Provides SQL connections based on XML configuration.
 */
final class SqlConnectionProvider
{
    /**
     * @var array<string,DataSource>
     */
    private array $dataSources = [];

    /**
     * @var array<string,Connection>
     */
    private array $connections = [];

    /**
     * @param \SimpleXMLElement $xml
     * @param string            $environment
     * @throws ConfigurationException
     */
    public function __construct(\SimpleXMLElement $xml, string $environment)
    {
        $xml = $xml->sql->{$environment};
        if (empty($xml)) {
            return;
        }
        if (!$xml->server) {
            throw new ConfigurationException("Server not set for environment!");
        }
        $this->dataSources = $this->getDataSources($xml);
    }

    /**
     * @param string $serverName
     * @return Connection
     * @throws Exception
     * @throws \Lucinda\SQL\ConnectionException
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
                $name = (string) $element["name"];
                if (!$name) {
                    throw new ConfigurationException("Attribute 'name' is mandatory for 'server' tag");
                }
                $output[$name] = new DataSource($element);
            }
        } else {
            $output[""] = new DataSource($xml["server"]);
        }
        return $output;
    }

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
