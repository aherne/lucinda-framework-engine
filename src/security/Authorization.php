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
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @param integer|string $userID Unique logged in user identifier (generally a number) or null (if user performing request isn't authenticated)
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @throws SecurityPacket If authorization encounters a situation where execution cannot continue and redirection is required
     */
    public function __construct(\SimpleXMLElement $xml, \Lucinda\MVC\STDOUT\Request $request, $userID)
    {
        $wrapper = $this->getWrapper($xml, $request, $userID);
        $this->authorize($wrapper, $request);
    }
    /**
     * Gets driver that performs authorization from security.authorization XML tag.
     *
     * @param \SimpleXMLElement $xmlRoot XML holding information relevant to authorization (above all via security.authorization tag)
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @param mixed $userID Unique logged in user identifier (generally a number) or null (if user performing request isn't authenticated)
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
     * @throws \Lucinda\SQL\StatementException If query to database server fails.
     * @return AuthorizationWrapper
     */
    protected function getWrapper(\SimpleXMLElement $xmlRoot, \Lucinda\MVC\STDOUT\Request $request, $userID)
    {
        $xml = $xmlRoot->authorization;
        if (empty($xml)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'authorization' child of 'security' tag is empty or missing");
        }
        
        $wrapper = null;
        if ($xml->by_route) {
            require("authorization/XMLAuthorizationWrapper.php");
            $wrapper = new XMLAuthorizationWrapper(
                $xmlRoot,
                $request,
                $userID
            );
        }
        if ($xml->by_dao) {
            require("authorization/DAOAuthorizationWrapper.php");
            $wrapper = new DAOAuthorizationWrapper(
                $xmlRoot,
                $request,
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
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated request made by client
     * @throws SecurityPacket If authorization encounters a situation where execution cannot continue and redirection is required
     */
    protected function authorize(AuthorizationWrapper $wrapper, \Lucinda\MVC\STDOUT\Request $request)
    {
        if ($wrapper->getResult()->getStatus() == \Lucinda\WebSecurity\AuthorizationResultStatus::OK) {
            // authorization was successful
            return;
        } else {
            // authorization failed
            $transport = new SecurityPacket();
            $transport->setCallback($request->getURI()->getContextPath()."/".$wrapper->getResult()->getCallbackURI());
            $transport->setStatus($wrapper->getResult()->getStatus());
            throw $transport;
        }
    }
}
