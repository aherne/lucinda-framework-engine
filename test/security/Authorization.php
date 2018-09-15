<?php
require_once(str_replace("/test/","/src/",__FILE__));
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/security/loader.php");
require_once(dirname(dirname(__DIR__))."/src/security/SecurityPacket.php");

$_SERVER["REQUEST_METHOD"] = "GET";

$xml = simplexml_load_file("configuration.xml");

// authorization success
new Lucinda\Framework\Authorization($xml, "index", "/", 1);
echo __LINE__.": Y\n";

// authorization failed due to user not logged in
try {
    new Lucinda\Framework\Authorization($xml, "index", "/", 0);
} catch(Lucinda\Framework\SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "unauthorized"?"Y":"N")."\n";
}

// authorization failed due to logged in user not having rights to access page 
try {
    new Lucinda\Framework\Authorization($xml, "private", "/", 1);
} catch(Lucinda\Framework\SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "forbidden"?"Y":"N")."\n";
}

// authorization failed due to page not found in db
try {
    new Lucinda\Framework\Authorization($xml, "missing", "/", 0);
} catch(Lucinda\Framework\SecurityPacket $e) {
    echo __LINE__.": ".($e->getStatus() == "not_found"?"Y":"N")."\n";
}