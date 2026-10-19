<?php

namespace Lucinda\Framework;

use Lucinda\Framework\Json\Exception;

/**
 * Encodes and decodes JSON with exception-based error handling
 *
 * Encoded output preserves Unicode characters, while decoded JSON is returned
 * as associative PHP data. Invalid values raise JsonException instead of being
 * represented by a false or null error sentinel.
 */
final class Json
{
    /**
     * Encodes a PHP value as JSON while preserving Unicode characters
     *
     * @param mixed $data Value to serialize
     * @return string Serialized JSON document
     * @throws \JsonException If the value cannot be represented as JSON
     */
    public function encode(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }

    /**
     * Decodes a JSON document into associative PHP data
     *
     * JSON objects are represented as associative arrays and nested documents
     * may be at most 512 levels deep.
     *
     * @param string $json JSON document to parse
     * @return mixed Decoded scalar, array, or null value
     * @throws \JsonException If the document is invalid or exceeds the maximum depth
     */
    public function decode(string $json): mixed
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR|JSON_BIGINT_AS_STRING);
    }
}
