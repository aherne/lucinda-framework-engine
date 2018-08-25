<?php
require_once("LocaleDetector.php");
require_once("SettingsDetector.php");

/**
 * Binds Internationalization API with MVC STDOUT API (aka Servlets API) in order to be able to produce a localizable response via GETTEXT
 */
class LocalizationBinder
{
    /**
     * @param Application $application
     * @param Request $request
     * @throws ApplicationException
     */
    public function __construct(Application $application, Request $request) {
        // parses XML
        $xml = $application->getXML()->internationalization;
        if(empty($xml)) throw new ApplicationException("Tag missing/empty in configuration.xml: internationalization");
        
        // identifies locale
        $localeDetector = new LocaleDetector($xml, $request);
        
        // compiles settings
        $detector = new SettingsDetector($xml, $localeDetector);
        $settings = $detector->getSettings();
        $locale = $settings->getPreferredLocale();
        if(!file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$settings->getPreferredLocale())) {
            // if input locale is not supported, use default
            if(!file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$settings->getDefaultLocale())) {
                throw new ApplicationException("Translations not set for default locale: ".$settings->getDefaultLocale());
            }
            $locale = $settings->getDefaultLocale();
        }
        
        // saves locale in session
        if($localeDetector->getDetectionMethod() == "session") {
            $request->getSession()->set(LocaleDetector::PARAMETER_NAME, $locale);
        }
        
        // sets reader instance
        Lucinda\Internationalization\Reader::setInstance($settings, ($locale==$settings->getDefaultLocale()));
    }
}

