<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/security/src/authentication/DAOAuthentication.php");
require_once("vendor/lucinda/security/src/authorization/UserAuthorizationRoles.php");
require_once("vendor/lucinda/security/src/token/TokenException.php");
require_once("AuthenticationWrapper.php");
require_once("form/FormRequestValidator.php");
require_once("form/LoginThrottlerHandler.php");

/**
 * Binds DAOAuthentication @ SECURITY-API to settings from configuration.xml @ SERVLETS-API then performs login/logout if it matches paths @ xml via database.
 */
class DAOAuthenticationWrapper extends AuthenticationWrapper
{
    private $driver;

    /**
     * Creates an object.
     *
     * @param \SimpleXMLElement $xml XML holding information relevant to authentication (above all via security.authentication tag)
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @param string $ipAddress Client ip address resolved from headers
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     */
    public function __construct(\SimpleXMLElement $xml, \Lucinda\MVC\STDOUT\Request $request, $ipAddress, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers)
    {
        // loads and instances DAO object
        $className = (string) $xml->authentication->form["dao"];
        load_class((string) $xml["dao_path"], $className);
        $authenticationDaoObject = new $className();
        if (!($authenticationDaoObject instanceof \Lucinda\WebSecurity\UserAuthenticationDAO)) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of UserAuthenticationDAO: ".$className);
        }

        // starts dao-based form authentication
        $this->driver = new \Lucinda\WebSecurity\DAOAuthentication($authenticationDaoObject, $persistenceDrivers);

        // setup class properties
        $validator = new FormRequestValidator($xml);
        
        // checks if a login action was requested, in which case it forwards object to driver
        if ($loginRequest = $validator->login($request)) {
            // check csrf token
            if (!$request->parameters("csrf") || !$csrfTokenDetector->isValid($request->parameters("csrf"), 0)) {
                throw new \Lucinda\WebSecurity\TokenException("CSRF token is invalid or missing!");
            }
            
            // performs login, using throttler if defined
            $className = (string) $xml->authentication->form["throttler"];
            if ($className) {
                $loginThrottlerHandler = new LoginThrottlerHandler($className, $request, $ipAddress, $loginRequest->getUsername());
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
     *
     * @param LoginRequest $request Encapsulates login request data.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     */
    private function login(LoginRequest $request)
    {
        $result = null;
        
        // set result
        $result = $this->driver->login(
            $request->getUsername(),
            $request->getPassword(),
            $request->getRememberMe()
            );
                
        $this->setResult($result, $request->getSourcePage(), $request->getDestinationPage());
    }

    /**
     * Logs user out authentication driver.
     *
     * @param LogoutRequest $request Encapsulates logout request data.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     */
    private function logout(LogoutRequest $request)
    {
        // set result
        $result = $this->driver->logout();
        $this->setResult($result, $request->getDestinationPage(), $request->getDestinationPage());
    }
}
