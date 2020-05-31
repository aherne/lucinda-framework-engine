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
     * @throws Exception If encoding of mixed data into json failed
     */
    public function encode($data): string
    {
        $result = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->checkError();
        return $result;
    }
    
    /**
     * Decodes JSON into original php data type.
     *
     * @param string $json
     * @param boolean $assoc
     * @return array
     * @throws Exception If decoding of json into array failed
     */
    public function decode(string $json, bool $assoc=true): array
    {
        $result = json_decode($json, $assoc);
        $this->checkError();
        return $result;
    }
    
    /**
     * Checks if encoding/decoding went without error. If error, throws JsonException.
     *
     * @throws Exception If decoding/encoding of json failed.
     */
    private function checkError(): void
    {
        $errorID = json_last_error();
        
        // everything went well
        if ($errorID == JSON_ERROR_NONE) {
            return;
        }
        
        throw new Exception(json_last_error_msg());
    }
}
