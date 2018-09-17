<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/internationalization/src/Reader.php");
require_once("LocaleDetector.php");
require_once("SettingsDetector.php");

/**
 * Binds Internationalization API with MVC STDOUT API (aka Servlets API) in order to be able to produce a localizable response via GETTEXT
 */
class LocalizationBinder
{
    /**
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @throws \Lucinda\MVC\STDOUT\XMLException
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request) {
        // parses XML
        $xml = $application->getTag("internationalization");
        if(empty($xml)) throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'internationalization' missing");
        
        // identifies locale
        $localeDetector = new LocaleDetector($xml, $request);
        
        // compiles settings
        $detector = new SettingsDetector($xml, $localeDetector);
        $settings = $detector->getSettings();
        $locale = $settings->getPreferredLocale();
        if(!file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$settings->getPreferredLocale())) {
            // if input locale is not supported, use default
            if(!file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$settings->getDefaultLocale())) {
                throw new \Lucinda\MVC\STDOUT\XMLException("Translations not set for default locale: ".$settings->getDefaultLocale());
            }
            $locale = $settings->getDefaultLocale();
        }
        
        // saves locale in session
        if($localeDetector->getDetectionMethod() == "session") {
            $request->getSession()->set(LocaleDetector::PARAMETER_NAME, $locale);
        }
        
        // sets reader instance
        \Lucinda\Internationalization\Reader::setInstance($settings, ($locale==$settings->getDefaultLocale()));
    }
}

