<?php
class MyLogger extends Lucinda\Logging\Logger {
    protected function log($info, $level)
    {
        echo __FILE__;
    }   
}