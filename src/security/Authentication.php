<?php
namespace Lucinda\Framework;

require_once(dirname(__DIR__)."/ClassLoader.php");
require_once("SecurityPacket.php");

/**
 * Performs user authentication based on mechanism chosen by developmer in XML (eg: from database via login form, from an oauth2 provider, etc)
 */
class Authentication
{
    /**
     * Runs authentication logic.
     *
     * @param \SimpleXMLElement $xml XML holding information relevant to authentication (above all via security.authentication tag)
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @param string $developmentEnvironment Current development environment (eg: local)
     * @param string $ipAddress Client ip address resolved from headers
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
     * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    public function __construct(\SimpleXMLElement $xml, \Lucinda\MVC\STDOUT\Request $request, $developmentEnvironment, $ipAddress, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers)
    {
        $wrappers = $this->getWrappers($xml, $request, $developmentEnvironment, $ipAddress, $csrfTokenDetector, $persistenceDrivers);
        foreach ($wrappers as $wrapper) {
            $this->authenticate($wrapper, $request, $persistenceDrivers);
        }
    }
    
    /**
     * Gets driver that performs authentication from security.authentication XML tag.
     *
     * @param \SimpleXMLElement $xmlRoot XML holding information relevant to authentication (above all via security.authentication tag)
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @param string $developmentEnvironment Current development environment (eg: local)
     * @param string $ipAddress Client ip address resolved from headers
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws \OAuth2\ClientException When oauth2 local client sends malformed requests to oauth2 server.
     * @throws \OAuth2\ServerException When oauth2 remote server answers with an error.
     * @return AuthenticationWrapper[]
     */
    protected function getWrappers(\SimpleXMLElement $xmlRoot, \Lucinda\MVC\STDOUT\Request $request, $developmentEnvironment, $ipAddress, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers)
    {
        $wrappers = array();
        $xml = $xmlRoot->authentication;
        if (empty($xml)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'authentication' child of 'security' is empty or missing");
        }
        
        $wrapper = null;
        if ($xml->form) {
            if ((string) $xml->form["dao"]) {
                require("authentication/DAOAuthenticationWrapper.php");
                $wrappers[] = new DAOAuthenticationWrapper(
                    $xmlRoot,
                    $request,
                    $ipAddress,
                    $csrfTokenDetector,
                    $persistenceDrivers
                    );
            } else {
                require("authentication/XMLAuthenticationWrapper.php");
                $wrappers[] = new XMLAuthenticationWrapper(
                    $xmlRoot,
                    $request,
                    $ipAddress,
                    $csrfTokenDetector,
                    $persistenceDrivers
                    );
            }
        }
        if ($xml->oauth2) {
            require("authentication/OAuth2AuthenticationWrapper.php");
            $wrappers[] = new OAuth2AuthenticationWrapper(
                $xmlRoot,
                $request,
                $developmentEnvironment,
                $csrfTokenDetector,
                $persistenceDrivers
                );
        }
        if (empty($wrappers)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("No authentication method chosen!");
        }
        return $wrappers;
    }
    
    /**
     * Calls authentication driver detected to perform user authentication.
     *
     * @param AuthenticationWrapper $wrapper Driver that performs authentication (eg: via form & database).
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    protected function authenticate(AuthenticationWrapper $wrapper, \Lucinda\MVC\STDOUT\Request $request, $persistenceDrivers)
    {
        if (!$wrapper->getResult()) {
            // no authentication was requested
            return;
        } else {
            // authentication was requested
            $transport = new SecurityPacket();
            if ($wrapper->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED) {
                $transport->setCallback($wrapper->getResult()->getCallbackURI());
            } else {
                $transport->setCallback($request->getURI()->getContextPath()."/".$wrapper->getResult()->getCallbackURI());
            }
            $transport->setStatus($wrapper->getResult()->getStatus());
            $transport->setAccessToken($wrapper->getResult()->getUserID(), $persistenceDrivers);
            $transport->setTimePenalty($wrapper->getResult()->getTimePenalty());
            throw $transport;
        }
    }
}
