<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/parameters-validator/loader.php");

/**
 * Binds Parameters Validation API with MVC STDOUT API (aka Servlets API) and stdout.xml in order to perform validation of path/request parameters 
 */
class ValidationBinder {
    private $results;
    
    /**
     * @param \Lucinda\MVC\STDOUT\Request $request
     */
    public function __construct(\Lucinda\MVC\STDOUT\Request $request) {
        $validator = new \Lucinda\ParameterValidator\Validator(
            "stdout.xml",
            $request->getValidator()->getPage(),
            $request->getMethod(),
            $this->getParameters($request));
        $this->results = $validator->getResults();
    }
    
    /**
     * Builds a compilation of path parameters and request method specific parameters. If param names are the same, path parameter version takes precedence.
     * 
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @return string[mixed] Parameters by name and value
     */
    private function getParameters(\Lucinda\MVC\STDOUT\Request $request) {
        $output = array();
        
        // get path parameters
        $pathParameters = $request->getValidator()->getPathParameters();
        $output = $pathParameters;
        
        // appends request parameters
        $requestParameters = $request->getParameters();
        foreach($requestParameters as $name=>$value) {
            if(isset($output[$name])) continue;
            $output[$name] = $value;
        }
        
        return $output;
    }
    
    /**
     * Gets validation results
     * 
     * @return \Lucinda\ParameterValidator\ResultsList
     */
    public function getResults() {
        return $this->results;
    }
}