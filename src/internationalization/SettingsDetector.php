<?php
namespace Lucinda\Framework;
/**
 * Binds Internationalization API Settings to <internationalization> tag content.
 */
class SettingsDetector {
    private $settings;
    
    /**
     * Saves detected internationalization settings based on contents of <internationalization> XML tag.
     * 
     * @param \SimpleXMLElement $xml Content of <internationalization> tag.
     * @param LocaleDetector $locale Locale detected previously by matching <internationalization> tag content with user request.
     */
    public function __construct(\SimpleXMLElement $xml, LocaleDetector $locale) {
        $this->setSettings($xml, $locale);
    }
    /**
     * Compiles and saves an Internationalization API Settings object based on arguments.
     *
     * @param \SimpleXMLElement $xml Content of <internationalization> tag.
     * @param LocaleDetector $locale Locale detected previously by matching <internationalization> tag content with user request.
     */
    
    private function setSettings(\SimpleXMLElement $xml, LocaleDetector $locale) {
        // compiles settings
        $this->settings = new \Lucinda\Internationalization\Settings($locale->getDetectedLocale(), $locale->getDefaultLocale());
        $domain = (string) $xml["domain"];
        if($domain) $this->settings->setDomain($domain);
        $folder = (string) $xml["folder"];
        if($folder) $this->settings->setFolder($folder);
    }
    
    /**
     * Gets compiled Internationalization API Settings object
     *
     * @return \Lucinda\Internationalization\Settings
     */
    public function getSettings() {
        return $this->settings;
    }
}