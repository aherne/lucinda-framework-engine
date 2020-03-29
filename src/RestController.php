<?php
namespace Lucinda\Framework;

/**
 * Defines an abstract RESTful controller. Classes extending it must have methods whose name is identical to request methods they are expecting.
 */
abstract class RestController extends \Lucinda\STDOUT\Controller
{
    /**
     * @var Attributes
     */
    protected $attributes;
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\STDOUT\Runnable::run()
     */
    public function run(): void
    {
        $this->response->view()["token"] = $this->attributes->getAccessToken();
        $methodName = strtoupper($this->request->getMethod());
        if (method_exists($this, $methodName)) {
            $this->$methodName();
        } else {
            throw new \Lucinda\STDOUT\MethodNotAllowedException();
        }
    }
    
    /**
     * Support HTTP OPTIONS requests by default
     */
    public function OPTIONS(): void
    {
        $options = array();
        $validHTTPMethods = array("GET","POST","PUT","DELETE","HEAD","OPTIONS","CONNECT","TRACE");
        foreach ($validHTTPMethods as $methodName) {
            if (method_exists($this, $methodName)) {
                $options[] = $methodName;
            }
        }
        $this->response->headers("Allow", implode(", ", $options));
    }
}
