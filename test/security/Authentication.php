<?php
require_once(str_replace("/test/","/src/",__FILE__));
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/security/loader.php");
require_once(dirname(dirname(__DIR__))."/src/security/CsrfTokenDetector.php");
require_once(dirname(dirname(__DIR__))."/src/security/PersistenceDriversDetector.php");
require_once(dirname(dirname(__DIR__))."/src/security/SecurityPacket.php");

$xml = simplexml_load_file("configuration.xml");

$csrf = new CsrfTokenDetector($xml);

$pdd = new PersistenceDriversDetector($xml);
$persistenceDrivers = $pdd->getPersistenceDrivers();

// no authentication requested
new Authentication($xml, "login", "/", $csrf, $persistenceDrivers);
echo __LINE__.": Y\n";

// login failed
$_POST = array("username"=>"lucian", "password"=>"epopescu", "csrf" => $csrf->generate(0));
try {
    new Authentication($xml, "login", "/", $csrf, $persistenceDrivers);
    echo __LINE__.": N\n";
} catch(SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "login_failed"?"Y":"N")."\n";
}

// login success
$_POST = array("username"=>"lucian", "password"=>"popescu", "csrf" => $csrf->generate(0));
try {
    new Authentication($xml, "login", "/", $csrf, $persistenceDrivers);
    echo __LINE__.": N\n";
} catch(SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "login_ok"?"Y":"N")."\n";
}

// logout success
try {
    new Authentication($xml, "logout", "/", $csrf, $persistenceDrivers);
} catch(SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "logout_ok"?"Y":"N")."\n";
}

// logout failed
try {
    new Authentication($xml, "logout", "/", $csrf, $persistenceDrivers);
} catch(SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "logout_failed"?"Y":"N")."\n";
}