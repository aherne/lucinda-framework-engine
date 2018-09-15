<?php
set_include_path(dirname(dirname(__DIR__)));
require_once(str_replace("/test/","/src/",__FILE__));

// create test environment
$xml = '
<xml>
    <security>
        <persistence>
            <session parameter_name="uid"/>
            <remember_me secret="asd" parameter_name="uid"/>
            <synchronizer_token secret="rrwe"/>
            <json_web_token secret="tyu"/>
        </persistence>
    </security>
</xml>
';

// csrf
$_SERVER = array("HTTP_X_FORWARDED_FOR"=>"82.76.206.3","REMOTE_ADDR"=>"192.168.21.211");
$pdd = new Lucinda\Framework\PersistenceDriversDetector(simplexml_load_string($xml)->security);
echo __LINE__.": ".($pdd->getPersistenceDrivers()[0] instanceof Lucinda\WebSecurity\SessionPersistenceDriver?"Y":"N")."\n";
echo __LINE__.": ".($pdd->getPersistenceDrivers()[1] instanceof Lucinda\WebSecurity\RememberMePersistenceDriver?"Y":"N")."\n";
echo __LINE__.": ".($pdd->getPersistenceDrivers()[2] instanceof Lucinda\WebSecurity\SynchronizerTokenPersistenceDriver?"Y":"N")."\n";
echo __LINE__.": ".($pdd->getPersistenceDrivers()[3] instanceof Lucinda\WebSecurity\JsonWebTokenPersistenceDriver?"Y":"N");