<?php
namespace Lucinda\Framework;

require("vendor/lucinda/security/src/authentication/XMLAuthentication.php");
require_once("vendor/lucinda/security/src/token/TokenException.php");
require_once("AuthenticationWrapper.php");
require_once("form/FormRequestValidator.php");
require_once("form/LoginThrottlerHandler.php");

/**
 * Binds XMLAuthentication @ SECURITY-API to settings from configuration.xml @ SERVLETS-API then performs login/logout if it matches paths @ xml via ACL @ XML.
 */
class XMLAuthenticationWrapper extends AuthenticationWrapper
{
    private $validator;
    private $driver;
    
    /**
     * Creates an object.
     *
     * @param \SimpleXMLElement $xml Contents of security.authentication.form tag @ configuration.xml.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @param string $ipAddress Client ip address resolved from headers
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail
     */
    public function __construct(\SimpleXMLElement $xml, \Lucinda\MVC\STDOUT\Request $request, $ipAddress, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers)
    {
        $xml = $xml->xpath("..")[0];
        
        // set driver
        $this->driver = new \Lucinda\WebSecurity\XMLAuthentication($xml, $persistenceDrivers);
        
        // setup class properties
        $validator = new FormRequestValidator($xml->security);
        
        // checks if a login action was requested, in which case it forwards object to driver
        if ($loginRequest = $validator->login($request)) {
            // check csrf token
            if (!$request->parameters("csrf") || !$csrfTokenDetector->isValid($request->parameters("csrf"), 0)) {
                throw new \Lucinda\WebSecurity\TokenException("CSRF token is invalid or missing!");
            }
                        
            // performs login, using throttler if defined
            $className = (string) $xml->authentication->form["throttler"];
            if ($className) {
                $loginThrottlerHandler = new LoginThrottlerHandler((string) $xml["dao_path"], $className, $request, $ipAddress, $loginRequest->getUsername());
                $this->result = $loginThrottlerHandler->start($request);
                if ($this->result) {
                    return;
                }
                $this->login($loginRequest);
                $loginThrottlerHandler->end($this->result);
            } else {
                $this->login($loginRequest);
            }    
        }
        
        // checks if a logout action was requested, in which case it forwards object to driver
        if ($logoutRequest = $validator->logout($request)) {
            $this->logout($logoutRequest);
        }
    }
    
    /**
     * Logs user in authentication driver.
     */
    private function login(LoginRequest $request)
    {
        // set result
        $result = $this->driver->login(
            $request->getUsername(),
            $request->getPassword()
            );
        $this->setResult($result, $request->getSourcePage(), $request->getDestinationPage());
    }
    
    /**
     * Logs user out authentication driver.
     *
     * @param LogoutRequest $request Encapsulates logout request data.
     */
    private function logout(LogoutRequest $request)
    {
        // set result
        $result = $this->driver->logout();
        $this->setResult($result, $request->getDestinationPage(), $request->getDestinationPage());
    }
}
