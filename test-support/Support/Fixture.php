<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\STDOUT\Request;

final class Fixture
{
    private const XML_FILE = __DIR__."/../mocks/fixtures.xml";

    private static ?\SimpleXMLElement $document = null;

    public static function node(string $fixture): \SimpleXMLElement
    {
        if (!preg_match('/\A[a-z0-9-]+\z/', $fixture)) {
            throw new \InvalidArgumentException("Invalid XML test fixture name: ".$fixture);
        }

        $matches = self::document()->xpath("//*[@fixture='".$fixture."']");
        if (empty($matches)) {
            throw new \RuntimeException("Unknown XML test fixture: ".$fixture);
        }

        $xml = $matches[0]->asXML();
        if ($xml === false) {
            throw new \RuntimeException("Unable to copy XML test fixture: ".$fixture);
        }

        $node = new \SimpleXMLElement($xml);
        unset($node["fixture"]);

        return $node;
    }

    public static function webRequest(
        string $authorization = "",
        bool $withUserAgent = true
    ): Request {
        $_GET = ["search" => "framework"];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [
            "HTTP_HOST" => "www.example.com",
            "SERVER_ADMIN" => "",
            "SERVER_SOFTWARE" => "unit-test",
            "SERVER_NAME" => "www.example.com",
            "SERVER_ADDR" => "127.0.0.1",
            "SERVER_PORT" => "80",
            "REMOTE_ADDR" => "192.0.2.10",
            "REMOTE_PORT" => "54321",
            "REQUEST_SCHEME" => "http",
            "REQUEST_URI" => "/app/articles?search=framework",
            "REQUEST_METHOD" => "GET",
            "DOCUMENT_ROOT" => "/var/www",
            "SCRIPT_FILENAME" => "/var/www/app/index.php",
            "QUERY_STRING" => "search=framework"
        ];

        if ($authorization !== "") {
            $_SERVER["HTTP_AUTHORIZATION"] = $authorization;
        }
        if ($withUserAgent) {
            $_SERVER["HTTP_USER_AGENT"] = "Framework Engine Test";
        }

        return new Request();
    }

    private static function document(): \SimpleXMLElement
    {
        if (self::$document === null) {
            $document = simplexml_load_file(self::XML_FILE, \SimpleXMLElement::class, LIBXML_NONET);
            if ($document === false) {
                throw new \RuntimeException("Unable to load XML test fixtures");
            }
            self::$document = $document;
        }

        return self::$document;
    }
}
