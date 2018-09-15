<?php
namespace Lucinda\Framework;
require_once("DataSourceDetection.php");

/**
 * Encapsulates NoSQLDataSource detection (itself encapsulating database server settings) based on <server> XML tag contents
 */
class NoSQLDataSourceDetection extends DataSourceDetection {    
    protected function setDataSource(\SimpleXMLElement $databaseInfo) {
        $driver = (string) $databaseInfo["driver"];
        if(!$driver) throw new \Lucinda\MVC\STDOUT\XMLException("Child tag <driver> is mandatory for <server> tags!");
        switch($driver) {
            case "couchbase":
                $host = (string) $databaseInfo["host"];
                $userName = (string) $databaseInfo["username"];
                $password = (string) $databaseInfo["password"];
                $bucket = (string) $databaseInfo["bucket_name"];
                if(!$host || !$userName || !$password || !$bucket) throw new \Lucinda\MVC\STDOUT\XMLException("For COUCHBASE driver following attributes are mandatory: host, username, password, bucket_name");
                
                require_once("vendor/lucinda/nosql-data-access/src/CouchbaseDriver.php");
                
                $dataSource = new \Lucinda\NoSQL\CouchbaseDataSource();
                $dataSource->setHost($host);
                $dataSource->setAuthenticationInfo($userName, $password);
                $dataSource->setBucketInfo($bucket, (string) $databaseInfo["bucket_password"]);
                $this->dataSource = $dataSource;
                break;
            case "memcache":
                require_once("vendor/lucinda/nosql-data-access/src/MemcacheDriver.php");
                
                $dataSource = new \Lucinda\NoSQL\MemcacheDataSource();
                $this->setServerInfo($databaseInfo, $dataSource);
                $this->dataSource = $dataSource;
                break;
            case "memcached":
                require_once("vendor/lucinda/nosql-data-access/src/MemcachedDriver.php");
                
                $dataSource = new \Lucinda\NoSQL\MemcachedDataSource();
                $this->setServerInfo($databaseInfo, $dataSource);
                $this->dataSource = $dataSource;
                break;
            case "redis":
                require_once("vendor/lucinda/nosql-data-access/src/RedisDriver.php");
                
                $dataSource = new \Lucinda\NoSQL\RedisDataSource();
                $this->setServerInfo($databaseInfo, $dataSource);
                $this->dataSource = $dataSource;
                break;
            case "apc":
                require_once("vendor/lucinda/nosql-data-access/src/APCDriver.php");
                
                $this->dataSource = new \Lucinda\NoSQL\APCDataSource();
                break;
            case "apcu":
                require_once("vendor/lucinda/nosql-data-access/src/APCuDriver.php");
                
                $this->dataSource = new \Lucinda\NoSQL\APCuDataSource();
                break;
            default:
                throw new \Lucinda\MVC\STDOUT\XMLException("Nosql driver not supported: ".$driver);
                break;
        }
    }
    
    private function setServerInfo(\SimpleXMLElement $databaseInfo, \Lucinda\NoSQL\DataSource $dataSource) {
        // set host and ports
        $temp = (string) $databaseInfo["host"];
        if(!$temp) throw new \Lucinda\MVC\STDOUT\XMLException("Driver attribute 'host' is mandatory");
        $hosts = explode(",",$temp);
        foreach($hosts as $hostAndPort) {
            $hostAndPort = trim($hostAndPort);
            $position = strpos($hostAndPort,":");
            if($position!==false) {
                $dataSource->addServer(substr($hostAndPort, 0, $position), substr($hostAndPort,$position+1));
            } else {
                $dataSource->addServer($hostAndPort);
            }
        }
        
        // set timeout
        $timeout= (string) $databaseInfo["timeout"];
        if($timeout) {
            $dataSource->setTimeout($timeout);
        }
        
        // set persistent
        $persistent = (string) $databaseInfo["persistent"];
        if($persistent) {
            $dataSource->setPersistent();
        }
    }
}