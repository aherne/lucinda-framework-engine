<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\Json;
use Lucinda\UnitTest\Result;

final class JsonTest
{
    private Json $json;

    public function __construct()
    {
        $this->json = new Json();
    }

    public function encode()
    {
        $encoded = $this->json->encode(["message" => "Bună ziua"]);

        try {
            $this->json->encode(INF);
            $invalidValueRejected = false;
        } catch (\JsonException) {
            $invalidValueRejected = true;
        }

        return [
            new Result($encoded === '{"message":"Bună ziua"}', "unicode is preserved"),
            new Result($invalidValueRejected, "invalid value is rejected")
        ];
    }

    public function decode()
    {
        $decoded = $this->json->decode(
            '{"name":"framework","id":9223372036854775808}'
        );

        try {
            $this->json->decode('{"invalid":}');
            $malformedDocumentRejected = false;
        } catch (\JsonException) {
            $malformedDocumentRejected = true;
        }

        return [
            new Result($decoded["name"] === "framework", "objects become arrays"),
            new Result(
                $decoded["id"] === "9223372036854775808",
                "large integer precision"
            ),
            new Result($malformedDocumentRejected, "malformed document is rejected")
        ];
    }
}
