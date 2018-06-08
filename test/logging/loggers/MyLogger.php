<?php
class MyLogger extends Logger {
    protected function log($info, $level)
    {
        echo __FILE__;
    }   
}