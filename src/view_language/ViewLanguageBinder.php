<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/view-language/loader.php");
require_once("ViewLanguageWrapper.php");

/**
 * Binds View Language API with MVC STDOUT API (aka Servlets API) in order to be process a templated HTML view and alter response accordingly
 */
class ViewLanguageBinder {
    /**
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Response $response
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Response $response) {        
        // get compilation file
        $wrapper = new ViewLanguageWrapper($application->getTag("application"), $response->getView());
        $compilationFile = $wrapper->getCompilationFile();
                  
        // commits response to output stream
        $data = $response->attributes()->toArray();
        ob_start();
        require_once($compilationFile);
        $response->getOutputStream()->set(ob_get_contents());
        ob_end_clean();
    }
}