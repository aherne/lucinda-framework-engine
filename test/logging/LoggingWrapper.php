<?php
require_once(str_replace("/test/","/src/",__FILE__));
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/logging/loader.php");
set_include_path(dirname(dirname(__DIR__)));
// create test environment
$xml = '
<xml>
    <loggers>
        <local custom_path = "loggers">
            <logger class="FileLoggerWrapper" path="test" format="%d %m"/>
            <logger class="SysLoggerWrapper" application="test" format="%d %m"/>
            <logger class="MyLoggerWrapper" />
        </local>
    </loggers>
</xml>
';

$xml = simplexml_load_string($xml);

$lw = new LoggingWrapper($xml->loggers->local);
echo __LINE__.": ".($lw->getLoggers()[0] instanceof FileLogger?"Y":"N")."\n";
echo __LINE__.": ".($lw->getLoggers()[1] instanceof SysLogger?"Y":"N")."\n";
echo __LINE__.": ".($lw->getLoggers()[2] instanceof MyLogger?"Y":"N")."\n";

