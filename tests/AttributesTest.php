<?php
namespace Test\Lucinda\Framework;

use Lucinda\Framework\Attributes;
use Lucinda\UnitTest\Result;

class AttributesTest
{
    private $object;
    
    public function __construct()
    {
        $this->object = new Attributes(__DIR__);
    }

    public function setHeaders()
    {
        $wrapper = new \Lucinda\Headers\Wrapper(simplexml_load_string('
<xml>
    <headers cache_expiration="10"/>
</xml>
'), "index", ["Host"=>"www.example.com"]);
        $this->object->setHeaders($wrapper);
        return new Result(true);
    }
        

    public function getHeaders()
    {
        return new Result($this->object->getHeaders()->getRequest()->getHost()=="www.example.com");
    }
        

    public function setLogger()
    {
        $wrapper = new \Lucinda\Logging\Wrapper(simplexml_load_string('
<xml>
  <loggers>
  	<local>
      	<logger class="Lucinda\Logging\Driver\File\Wrapper" path="messages" format="%d %v %e %f %l %m %u %i %a"/>
  	</local>
  </loggers>
</xml>
'), "local");
        $this->object->setLogger($wrapper->getLogger());
        return new Result(true);
    }
        

    public function getLogger()
    {
        return new Result($this->object->getLogger() instanceof \Lucinda\Logging\MultiLogger);
    }
        

    public function setUserId()
    {
        $this->object->setUserId(1);
        return new Result(true);
    }
        

    public function getUserId()
    {
        return new Result($this->object->getUserId()==1);
    }
        

    public function setCsrfToken()
    {
        $this->object->setCsrfToken("qwerty");
        return new Result(true);
    }
        

    public function getCsrfToken()
    {
        return new Result($this->object->getCsrfToken()=="qwerty");
    }
        

    public function setAccessToken()
    {
        $this->object->setAccessToken("qwerty");
        return new Result(true);
    }
        

    public function getAccessToken()
    {
        return new Result($this->object->getAccessToken()=="qwerty");
    }
}
