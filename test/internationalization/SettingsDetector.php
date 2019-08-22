<?php
require_once(str_replace("/test/", "/src/", __FILE__));
require_once(dirname(__DIR__)."/request.php");
require_once(dirname(dirname(__DIR__))."/src/internationalization/LocaleDetector.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/internationalization/src/Settings.php");

$xml = '<internationalization locale="en_US" method="header" folder="locale" domain="messages"/>';
$localeDetector = new Lucinda\Framework\LocaleDetector(simplexml_load_string($xml), new Lucinda\MVC\STDOUT\Request());

$settingsDetector = new Lucinda\Framework\SettingsDetector(simplexml_load_string($xml), $localeDetector);
echo __LINE__.": ".($settingsDetector->getSettings()->getDomain()=="messages"?"Y":"N")."\n";
echo __LINE__.": ".($settingsDetector->getSettings()->getFolder()==="locale"?"Y":"N")."\n";
echo __LINE__.": ".($settingsDetector->getSettings()->getExtension()==="json"?"Y":"N")."\n";
