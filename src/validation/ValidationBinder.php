<?php
namespace Lucinda\Framework;

require("vendor/lucinda/request-validator/src/Validator.php");

/**
 * Binds Parameters Validation API with MVC STDOUT API (aka Servlets API) and stdout.xml in order to perform validation of path/request parameters
 */
class ValidationBinder
{
    private $results;
    
    /**
     * Binds APIs to XML in order to perform request parameters validation based on contents of <route> tag.
     *
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @param string $xmlFilePath
     * @throws \Lucinda\RequestValidator\Exception If XML is misconfigured
     * @throws \Lucinda\RequestValidator\MethodNotSupportedException If http method used to retrieve resource is not supported.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Request $request, $xmlFilePath = "stdout.xml")
    {
        $validator = new \Lucinda\RequestValidator\Validator(
            $xmlFilePath,
            $request->getValidator()->getPage(),
            $request->getMethod(),
            $this->getParameters($request)
        );
        $this->results = $validator->getResults();
    }
    
    /**
     * Builds a compilation of path parameters and request method specific parameters. If param names are the same, path parameter version takes precedence.
     *
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @return string[mixed] Parameters by name and value
     */
    private function getParameters(\Lucinda\MVC\STDOUT\Request $request)
    {
        $output = array();
        
        // get path parameters
        $pathParameters = $request->getValidator()->parameters();
        $output = $pathParameters;
        
        // appends request parameters
        $requestParameters = $request->parameters();
        foreach ($requestParameters as $name=>$value) {
            if (isset($output[$name])) {
                continue;
            }
            $output[$name] = $value;
        }
        
        return $output;
    }
    
    /**
     * Gets validation results
     *
     * @return \Lucinda\RequestValidator\ResultsList
     */
    public function getResults()
    {
        return $this->results;
    }
}
