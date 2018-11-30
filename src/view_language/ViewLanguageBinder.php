<?php
namespace Lucinda\Framework;
require_once("vendor/lucinda/view-language/loader.php");

/**
 * Binds contents of <application> XML tag with detected environment then uses ViewLanguageAPI to produce a PHP file where templating logic in view
 * is compiled into PHP language.
 */
class ViewLanguageBinder {
    private $compilationFile;
    
    /**
     * @param \SimpleXMLElement $xml XML file holding compiler settings.
     * @param string $viewFile View file location (without extension, optionally including views folder path)
     */
    public function __construct(\SimpleXMLElement $xml, $viewFile) {
        $this->setCompilationFile($xml, $viewFile);
    }
    
    /**
     * Reads XML then delegates to ViewLanguageAPI to compile a templated view recursively into a PHP file.
     * 
     * @param \SimpleXMLElement $xml XML file holding compiler settings.
     * @param string $viewFile View file location (without extension, optionally including views folder path)
     * @throws \Lucinda\MVC\STDOUT\XMLException
     */
    private function setCompilationFile(\SimpleXMLElement $xml, $viewFile) {
        // get settings necessary in compilation
        $compilationsFolder = (string) $xml->paths->compilations;
        if(!$compilationsFolder) throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'compilations' child of 'paths' child of 'application' tags is empty or missing");
        $tagsFolder = (string) $xml->paths->tags;
        $viewsFolder = (string) $xml->paths->views;
        $extension = (string) $xml["templates_extension"];
        if(!$extension) throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'templates_extension' is missing for 'application' tag");
        
        // gets view file
        if($viewsFolder && strpos($viewFile, $viewsFolder)===0) {
            $viewFile = substr($viewFile, strlen($viewsFolder)+1);
        }
        
        // compiles templates recursively into a single compilation file
        $vlp = new \Lucinda\Templating\ViewLanguageParser($viewsFolder, $extension, $compilationsFolder, $tagsFolder);
        $this->compilationFile = $vlp->compile($viewFile);
    }
    
    /**
     * Gets compilation file path, where all ViewLanguage templating has been recursively compiled into PHP
     * 
     * @return string
     */
    public function getCompilationFile() {
        return $this->compilationFile;
    }
}
