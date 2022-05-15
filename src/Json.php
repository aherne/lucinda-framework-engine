<?php

namespace Lucinda\Framework;

use Lucinda\Framework\Json\Exception;

/**
 * Simple wrapper over json functionality.
 */
class Json
{
    /**
     * Encodes data into JSON format.
     *
     * @param mixed $data
     * @return string
     * @throws \JsonException If encoding of mixed data into json failed
     */
    public function encode(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }

    /**
     * Decodes JSON into original php data type.
     *
     * @param string $json
     * @return mixed
     * @throws \JsonException If decoding of json into array failed
     */
    public function decode(string $json): mixed
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
