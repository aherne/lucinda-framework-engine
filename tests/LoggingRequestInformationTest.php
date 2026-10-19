<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\LoggingRequestInformation;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\Support\Fixture;

final class LoggingRequestInformationTest
{
    public function getRequestInformation()
    {
        $server = $_SERVER;
        $get = $_GET;
        $post = $_POST;
        $files = $_FILES;

        try {
            Fixture::webRequest(withUserAgent: false);
            $web = (new LoggingRequestInformation())->getRequestInformation();

            $_GET = [];
            $_POST = [];
            $_FILES = [];
            $_SERVER = ["argv" => ["test.php", "scheduled-task"]];
            $console = (new LoggingRequestInformation())->getRequestInformation();

            return [
                new Result($web->getUri() === "articles", "web route"),
                new Result($web->getIpAddress() === "192.0.2.10", "web address"),
                new Result($web->getUserAgent() === "", "missing user agent"),
                new Result($console->getUri() === "scheduled-task", "console route")
            ];
        } finally {
            $_SERVER = $server;
            $_GET = $get;
            $_POST = $post;
            $_FILES = $files;
        }
    }
}
