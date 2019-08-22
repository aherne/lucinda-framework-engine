<?php
namespace Lucinda\Framework;

require_once(dirname(__DIR__)."/ClassLoader.php");
require_once("SecurityPacket.php");

/**
 * Performs request authorization based on mechanism chosen by developmer in XML (eg: from database)
 */
class Authorization
{
    /**
     * Runs authorization logic.
     *
     * @param \SimpleXMLElement $xml XML holding information relevant to authorization (above all via security.authorization tag)
     * @param string $page Route requested by client
     * @param string $contextPath \Lucinda\MVC\STDOUT\Application context path (default "/") necessary if multiple applications are deployed under same hostname
     * @param integer|string $userID Unique logged in user identifier (generally a number) or null (if user performing request isn't authenticated)
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid
     * @throws SecurityPacket If authorization encounters a situation where execution cannot continue and redirection is required
     */
    public function __construct(\SimpleXMLElement $xml, $page, $contextPath, $userID)
    {
        $wrapper = $this->getWrapper($xml, $page, $userID);
        $this->authorize($wrapper, $contextPath);
    }
    /**
     * Gets driver that performs authorization from security.authorization XML tag.
     *
     * @param \SimpleXMLElement $xmlRoot XML holding information relevant to authorization (above all via security.authorization tag)
     * @param string $page Route requested by client
     * @param mixed $userID Unique logged in user identifier (generally a number) or null (if user performing request isn't authenticated)
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid
     * @return AuthorizationWrapper
     */
    private function getWrapper(\SimpleXMLElement $xmlRoot, $page, $userID)
    {
        $xml = $xmlRoot->authorization;
        if (empty($xml)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'authorization' child of 'security' tag is empty or missing");
        }
        
        $wrapper = null;
        if ($xml->by_route) {
            require_once("authorization/XMLAuthorizationWrapper.php");
            $wrapper = new XMLAuthorizationWrapper(
                $xmlRoot,
                $page,
                $userID
            );
        }
        if ($xml->by_dao) {
            require_once("authorization/DAOAuthorizationWrapper.php");
            $wrapper = new DAOAuthorizationWrapper(
                $xmlRoot,
                $page,
                $userID
            );
        }
        if (!$wrapper) {
            throw new \Lucinda\MVC\STDOUT\XMLException("No authorization method chosen!");
        }
        return $wrapper;
    }
    
    /**
     * Calls authorization driver detected to perform user authorization to requested route.
     *
     * @param AuthenticationWrapper $wrapper Driver that performs authentication (eg: via form & database).
     * @param string $contextPath \Lucinda\MVC\STDOUT\Application context path (default "/") necessary if multiple applications are deployed under same hostname
     * @throws SecurityPacket If authorization encounters a situation where execution cannot continue and redirection is required
     */
    private function authorize(AuthorizationWrapper $wrapper, $contextPath)
    {
        if ($wrapper->getResult()->getStatus() == \Lucinda\WebSecurity\AuthorizationResultStatus::OK) {
            // authorization was successful
            return;
        } else {
            // authorization failed
            $transport = new SecurityPacket();
            $transport->setCallback($contextPath."/".$wrapper->getResult()->getCallbackURI());
            $transport->setStatus($wrapper->getResult()->getStatus());
            throw $transport;
        }
    }
}
