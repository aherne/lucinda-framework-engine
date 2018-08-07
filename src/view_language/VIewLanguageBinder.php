<?php
require_once("ViewLanguageWrapper.php");
require_once(dirname(__DIR__)."/Json.php");

/**
 * Binds View Language API with MVC STDOUT API (aka Servlets API) in order to be process a templated HTML view and alter response accordingly
 */
class ViewLanguageBinder {
    /**
     * @param Application $application
     * @param Response $response
     */
    public function __construct(Application $application, Response $response) {        
        // get compilation file
        $wrapper = new ViewLanguageWrapper($application->getXML(), $response->getView(), $application->getAttribute("environment"));
        $compilationFile = $wrapper->getCompilationFile();
        
        // converts objects sent to response into array (throws JsonException if object is non-convertible)
        $json = new Json();
        $data = $json->decode($json->encode($response->toArray()));
          
        // commits response to output stream
        ob_start();
        require_once($compilationFile);
        $response->getOutputStream()->set(ob_get_contents());
        ob_end_clean();
    }
}