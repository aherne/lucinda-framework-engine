<?php
namespace Test\Lucinda\Framework;
    
use Lucinda\Framework\SingletonRepository;
use Lucinda\UnitTest\Result;

class SingletonRepositoryTest
{

    public function set()
    {
        $object = new \stdClass();
        $object->test = "me";
        SingletonRepository::set("asd", $object);
        return new Result(true);
    }        

    public function get()
    {
        return new Result(SingletonRepository::get("asd")->test=="me");
    }
}
