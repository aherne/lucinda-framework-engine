<?php

namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\ServiceBinder;
use Lucinda\Framework\OAuth2\ServiceConverter;
use Lucinda\OAuth2\Client\Information as ClientInformation;
use Lucinda\OAuth2\Server\Information as ServerInformation;
use Lucinda\OAuth2\Vendor\Google\Driver;
use Lucinda\UnitTest\Result;

final class ServiceBinderTest
{
    public function getServices()
    {
        $services = (new ServiceBinder([$this->getDriver()]))->getServices();

        return [
            new Result(array_keys($services) === ["Google"], "provider name"),
            new Result($services["Google"] instanceof ServiceConverter, "converted service")
        ];
    }

    public function duplicateProvider()
    {
        try {
            new ServiceBinder([$this->getDriver(), $this->getDriver()]);
            return new Result(false, "duplicate provider was accepted");
        } catch (\LogicException $exception) {
            return new Result(
                $exception->getMessage() === "Duplicate OAuth2 provider: Google"
            );
        }
    }

    private function getDriver(): Driver
    {
        return new Driver(
            new ClientInformation("client-id", "client-secret", "https://app.example/callback"),
            new ServerInformation([
                "authorization_url" => "https://provider.example/authorize",
                "access_token_url" => "https://provider.example/token",
                "scopes" => ["openid"],
                "resource_url" => "https://provider.example/user"
            ])
        );
    }
}
