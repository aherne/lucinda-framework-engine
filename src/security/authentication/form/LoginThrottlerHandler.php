<?php
namespace Lucinda\Framework;

require_once("LoginThrottler.php");

/**
 * Encapsulates communication between LoginThrottler and \Lucinda\WebSecurity\AuthenticationResult instances
 */
class LoginThrottlerHandler
{
    const LOGIN_THROTTLERS_PATH = "application/models/throttlers";
    
    private $instance;
   
    /**
     * Loads and instances LoginThrottler based on information provided by arguments
     * 
     * @param string $daoPath Folder in which throttler class will be located.
     * @param string $className Login throttler class name.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @param string $ipAddress Client ip address resolved from headers
     * @param string $userName Username client has attempted
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct($className, $request, $ipAddress, $userName)
    {
        load_class(self::LOGIN_THROTTLERS_PATH, $className);
        $this->instance = new $className($request, $ipAddress, $userName);
        if (!($this->instance instanceof LoginThrottler)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of LoginThrottler: ".$className);
        }
    }
    
    /**
     * Asks throttler if client is liable for a new login attempt. If not, a login failed authentication result is generated!
     * 
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @return \Lucinda\WebSecurity\AuthenticationResult|null 
     */
    public function start(\Lucinda\MVC\STDOUT\Request $request)
    {
        if ($penalty = $this->instance->getTimePenalty()) {
            // set login as failed, without verifying
            $result = new \Lucinda\WebSecurity\AuthenticationResult(\Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_FAILED);
            $result->setCallbackURI($request->getValidator()->getPage());
            $result->setTimePenalty($penalty);
            return $result;
        }    
    }
    
    /**
     * Informs throttler about outcome of login attempt.
     * 
     * @param \Lucinda\WebSecurity\AuthenticationResult $result
     */
    public function end(\Lucinda\WebSecurity\AuthenticationResult $result)
    {
        $resultStatus =  $result->getStatus();
        if ($resultStatus == \Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_OK) {
            $this->instance->setSuccess();
        } else if ($resultStatus == \Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_FAILED) {
            $this->instance->setFailure();
        }
    }
}

