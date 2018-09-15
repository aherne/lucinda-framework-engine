<?php
set_include_path(dirname(dirname(__DIR__)));
require_once(str_replace("/test/","/src/",__FILE__));

// create test environment
$xml = '
<xml>
    <security>
        <csrf secret="asdf" expiration="10"/>
    </security>
</xml>
';

$_SERVER = array("HTTP_X_FORWARDED_FOR"=>"82.76.206.3","REMOTE_ADDR"=>"192.168.21.211");
$userID = 1;
$csrf = new Lucinda\Framework\CsrfTokenDetector(simplexml_load_string($xml)->security);
$token = $csrf->generate($userID);
echo __LINE__.": ".($csrf->isValid($token, $userID)?"Y":"N");