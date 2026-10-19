<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\RequestBinder;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Fixture;

final class RequestBinderTest
{
    public function getResult()
    {
        $bound = (new RequestBinder(
            Fixture::webRequest("bEaReR  provider-token  "),
            "articles/{id}"
        ))->getResult();

        $withoutAuthorization = (new RequestBinder(
            Fixture::webRequest(),
            "articles"
        ))->getResult();

        return [
            new Result($bound->getUri() === "articles/{id}", "route"),
            new Result($bound->getContextPath() === "/app", "context path"),
            new Result($bound->getIpAddress() === "192.0.2.10", "client address"),
            new Result($bound->getMethod() === "GET", "request method"),
            new Result(
                $bound->getParameters() === ["search" => "framework"],
                "parameters"
            ),
            new Result($bound->getAccessToken() === "provider-token", "bearer token"),
            new Result($withoutAuthorization->getAccessToken() === "", "missing token")
        ];
    }
}
