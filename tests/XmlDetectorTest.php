<?php
namespace Test\Lucinda\Framework;

use Lucinda\Framework\XmlDetector;
use Lucinda\UnitTest\Result;
use SimpleXMLElement;
    
class XmlDetectorTest
{

    public function getXML()
    {
        $xmlDetector = new XmlDetector(dirname(__DIR__)."/test-support/mocks/fixtures");
        return new Result($xmlDetector->getXML() instanceof SimpleXMLElement);
    }
        

}
