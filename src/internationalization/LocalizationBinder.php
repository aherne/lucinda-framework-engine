<?php
require_once("LocaleDetector.php");
require_once("SettingsDetector.php");

/**
 * Binds Internationalization API with MVC STDOUT API (aka Servlets API) in order to be able to produce a localizable response via GETTEXT
 */
class LocalizationBinder
{
    const PARAMETER_NAME = "locale";
    
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
        
        // identifies charset
        $charset = $application->getFormatInfo($application->getDefaultExtension())->getCharacterEncoding();
        
        // compiles settings
        $detector = new SettingsDetector($charset, $xml, $localeDetector);
        $settings = $detector->getSettings();
        
        // sets internationalization settings (throws LocaleException)
        new Lucinda\Internationalization\Reader($settings);
        
        // saves locale in session
        if($localeDetector->getDetectionMethod() == "session") {
            $request->getSession()->set(self::PARAMETER_NAME, $settings->getLocale());
        }
    }
}

