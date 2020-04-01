<?php
namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\DriverDetector;
use Lucinda\UnitTest\Result;

class DriverDetectorTest
{
    private $object;
    
    public function __construct()
    {
        $nativeDriver = new \Lucinda\OAuth2\Vendor\Facebook\Driver(new \Lucinda\OAuth2\Client\Information("asd", "fgh", "login/facebook"));
        $this->object = new DriverDetector(simplexml_load_string('
<xml>
    <security dao_path="'.__DIR__.'">
        <authentication>
            <oauth2 dao="'.__NAMESPACE__.'\\MockUserDAO"/>
        </authentication>
    </security>
</xml>
'), ["login/facebook"=>$nativeDriver], 1);
    }

    public function getAccessToken()
    {
        return new Result($this->object->getAccessToken()=="qwerty");
    }
        

    public function getDriver()
    {
        return new Result($this->object->getDriver() instanceof \Lucinda\OAuth2\Driver);
    }
}
