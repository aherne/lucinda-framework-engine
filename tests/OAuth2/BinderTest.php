<?php
namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\Binder;
use Lucinda\UnitTest\Result;

class BinderTest
{    
    public function getResults()
    {
        $nativeDriver = new \Lucinda\OAuth2\Vendor\Facebook\Driver(new \Lucinda\OAuth2\Client\Information("asd", "fgh", "login/facebook"));
        $binder  = new Binder(["login/facebook"=>$nativeDriver]);
        $results = $binder->getResults();
        return new Result($results[0] instanceof \Lucinda\Framework\OAuth2\Facebook\SecurityDriver);
    }   
}
