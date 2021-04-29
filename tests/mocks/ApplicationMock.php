<?php
use Lucinda\MVC\Application;

class ApplicationMock extends Application
{
    public function __construct(string $xmlFilePath)
    {
        $this->readXML($xmlFilePath);
    }
}

