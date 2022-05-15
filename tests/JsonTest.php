<?php

namespace Test\Lucinda\Framework;

use Lucinda\Framework\Json;
use Lucinda\UnitTest\Result;

class JsonTest
{
    private $object;

    public function __construct()
    {
        $this->object = new Json();
    }

    public function encode()
    {
        $result = $this->object->encode(["asd"=>"fgh"]);
        return new Result($result);
    }


    public function decode()
    {
        return new Result($this->object->decode($this->object->encode(["asd"=>"fgh"]))==["asd"=>"fgh"]);
    }
}
