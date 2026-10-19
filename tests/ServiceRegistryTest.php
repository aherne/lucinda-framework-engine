<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\ServiceRegistry;
use Lucinda\UnitTest\Result;

final class ServiceRegistryTest
{
    public function set()
    {
        ServiceRegistry::clear();
        $service = new \stdClass();
        ServiceRegistry::set("service", $service);

        return new Result(ServiceRegistry::get("service") === $service);
    }

    public function get()
    {
        ServiceRegistry::clear();
        $service = new \stdClass();
        ServiceRegistry::set("service", $service);

        try {
            ServiceRegistry::get("missing");
            $missingRejected = false;
        } catch (\RuntimeException) {
            $missingRejected = true;
        }

        return [
            new Result(ServiceRegistry::get("service") === $service, "registered service"),
            new Result($missingRejected, "missing service")
        ];
    }

    public function has()
    {
        ServiceRegistry::clear();
        ServiceRegistry::set("service", new \stdClass());

        return [
            new Result(ServiceRegistry::has("service"), "registered identifier"),
            new Result(!ServiceRegistry::has("missing"), "missing identifier")
        ];
    }

    public function clear()
    {
        ServiceRegistry::clear();
        ServiceRegistry::set("first", new \stdClass());
        ServiceRegistry::set("second", new \stdClass());
        ServiceRegistry::clear("first");
        $singleRemoval = !ServiceRegistry::has("first") && ServiceRegistry::has("second");
        ServiceRegistry::clear();

        return [
            new Result($singleRemoval, "single service"),
            new Result(!ServiceRegistry::has("second"), "all services")
        ];
    }
}
