<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/internationalization/src/Reader.php");
require_once("LocaleDetector.php");
require_once("SettingsDetector.php");

/**
 * Binds Internationalization API with MVC STDOUT API (aka Servlets API) in order to be able to produce a localizable response
 */
class LocalizationBinder
{
    /**
     * Sets up \Lucinda\Internationalization\Reader instance to use later on in automatic translation based on XML and client headers
     *
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request)
    {
        // parses XML
        $xml = $application->getTag("internationalization");
        if (empty($xml)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'internationalization' missing");
        }
        
        // identifies locale
        $localeDetector = new LocaleDetector($xml, $request);
        
        // compiles settings
        $detector = new SettingsDetector($xml, $localeDetector);
        $settings = $detector->getSettings();
        if (!file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$settings->getPreferredLocale())) {
            // if input locale is not supported, use default
            if (!file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$settings->getDefaultLocale())) {
                throw new \Lucinda\MVC\STDOUT\XMLException("Translations not set for default locale: ".$settings->getDefaultLocale());
            }
            $settings->setPreferredLocale($settings->getDefaultLocale()); // overrides not supported preferred locale with default
        }
        
        // saves locale in session
        if ($localeDetector->getDetectionMethod() == "session") {
            $request->getSession()->set(LocaleDetector::PARAMETER_NAME, $settings->getPreferredLocale());
        }
        
        // injects settings into reader for later translations
        \Lucinda\Internationalization\Reader::setSettings($settings);
    }
}
