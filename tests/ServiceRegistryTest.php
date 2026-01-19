<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\ServiceRegistry;
use Lucinda\UnitTest\Result;

class ServiceRegistryTest
{
    private const SERVICE_ID = "test.service";

    public function set()
    {
        ServiceRegistry::clear();
        $object = new \stdClass();
        ServiceRegistry::set(self::SERVICE_ID, $object);
        return new Result(true, "service registered");
    }

    public function get()
    {
        ServiceRegistry::clear();
        $object = new \stdClass();
        ServiceRegistry::set(self::SERVICE_ID, $object);
        return new Result(ServiceRegistry::get(self::SERVICE_ID) === $object);
    }

    public function has()
    {
        ServiceRegistry::clear();
        $object = new \stdClass();
        ServiceRegistry::set(self::SERVICE_ID, $object);
        return new Result(ServiceRegistry::has(self::SERVICE_ID));
    }

    public function clear()
    {
        ServiceRegistry::clear();
        $object = new \stdClass();
        ServiceRegistry::set(self::SERVICE_ID, $object);
        ServiceRegistry::clear(self::SERVICE_ID);
        $missing = !ServiceRegistry::has(self::SERVICE_ID);
        ServiceRegistry::set(self::SERVICE_ID, $object);
        ServiceRegistry::clear();
        $allCleared = !ServiceRegistry::has(self::SERVICE_ID);
        return new Result($missing && $allCleared);
    }
}
