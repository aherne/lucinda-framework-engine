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
     * @param string $developmentEnvironment
     * @param string $page Route requested by client
     * @param string $contextPath \Lucinda\MVC\STDOUT\Application context path (default "/") necessary if multiple applications are deployed under same hostname
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    public function __construct(\SimpleXMLElement $xml, $developmentEnvironment, $page, $contextPath, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers)
    {
        $wrappers = $this->getWrappers($xml, $developmentEnvironment, $page, $csrfTokenDetector, $persistenceDrivers);
        foreach ($wrappers as $wrapper) {
            $this->authenticate($wrapper, $contextPath, $persistenceDrivers);
        }
    }
    
    /**
     * Gets driver that performs authentication from security.authentication XML tag.
     *
     * @param \SimpleXMLElement $xmlRoot XML holding information relevant to authentication (above all via security.authentication tag)
     * @param string $developmentEnvironment
     * @param string $page Route requested by client
     * @param CsrfTokenDetector $csrfTokenDetector Driver performing CSRF validation
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid
     * @return AuthenticationWrapper[]
     */
    private function getWrappers(\SimpleXMLElement $xmlRoot, $developmentEnvironment, $page, CsrfTokenDetector $csrfTokenDetector, $persistenceDrivers)
    {
        $wrappers = array();
        $xml = $xmlRoot->authentication;
        if (empty($xml)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'authentication' child of 'security' is empty or missing");
        }
        
        $wrapper = null;
        if ($xml->form) {
            if ((string) $xml->form["dao"]) {
                require_once("authentication/DAOAuthenticationWrapper.php");
                $wrappers[] = new DAOAuthenticationWrapper(
                    $xmlRoot,
                    $page,
                    $persistenceDrivers,
                    $csrfTokenDetector
                    );
            } else {
                require_once("authentication/XMLAuthenticationWrapper.php");
                $wrappers[] = new XMLAuthenticationWrapper(
                    $xmlRoot,
                    $page,
                    $persistenceDrivers,
                    $csrfTokenDetector
                    );
            }
        }
        if ($xml->oauth2) {
            require_once("authentication/OAuth2AuthenticationWrapper.php");
            $wrappers[] = new OAuth2AuthenticationWrapper(
                $xmlRoot,
                $developmentEnvironment,
                $page,
                $persistenceDrivers,
                $csrfTokenDetector
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
     * @param string $contextPath \Lucinda\MVC\STDOUT\Application context path (default "/") necessary if multiple applications are deployed under same hostname
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers Drivers where authenticated state is persisted (eg: session, remember me cookie).
     * @throws SecurityPacket If authentication encounters a situation where execution cannot continue and redirection is required
     */
    private function authenticate(AuthenticationWrapper $wrapper, $contextPath, $persistenceDrivers)
    {
        if (!$wrapper->getResult()) {
            // no authentication was requested
            return;
        } else {
            // authentication was requested
            $transport = new SecurityPacket();
            $transport->setCallback($wrapper->getResult()->getStatus()==\Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED?$wrapper->getResult()->getCallbackURI():$contextPath."/".$wrapper->getResult()->getCallbackURI());
            $transport->setStatus($wrapper->getResult()->getStatus());
            $transport->setAccessToken($wrapper->getResult()->getUserID(), $persistenceDrivers);
            throw $transport;
        }
    }
}
