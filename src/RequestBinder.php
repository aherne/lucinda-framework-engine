<?php
namespace Lucinda\Framework;

/**
 * Binds Lucinda\STDOUT\Request to Lucinda\WebSecurity\Request
 */
class RequestBinder
{
    private $result;
    
    /**
     * Kick-starts binding process based on arguments
     *
     * @param \Lucinda\STDOUT\Request $request
     * @param string $validPage
     */
    public function __construct(\Lucinda\STDOUT\Request $request, string $validPage)
    {
        $accessToken = $this->getAccessToken($request);
        $this->setResult($request, $validPage, $accessToken);
    }
    
    /**
     * Gets access token based on Authorization request header received from client. Eg:
     * Authorization Bearer asdadasdasdasdasdasdasd
     *
     * @param \Lucinda\STDOUT\Request $request
     * @return string
     */
    private function getAccessToken(\Lucinda\STDOUT\Request $request): string
    {
        $accessToken = "";
        $header = $request->headers("Authorization");
        if ($header && stripos($header, "Bearer ")===0) {
            $accessToken = trim(substr($header, 7));
        }
        return $accessToken;
    }
    
    /**
     * Performs binding process between \Lucinda\STDOUT\Request and \Lucinda\WebSecurity\Request
     *
     * @param \Lucinda\STDOUT\Request $request
     * @param string $validPage
     * @param string $accessToken
     */
    private function setResult(\Lucinda\STDOUT\Request $request, string $validPage, string $accessToken): void
    {
        $requestBound = new \Lucinda\WebSecurity\Request();
        $requestBound->setUri($validPage);
        $requestBound->setMethod($request->getMethod());
        $requestBound->setParameters($request->parameters());
        $requestBound->setContextPath($request->getURI()->getContextPath());
        $requestBound->setAccessToken($accessToken);
        $requestBound->setIpAddress($request->getClient()->getIP());
        $this->result = $requestBound;
    }
    
    /**
     * Gets binding result
     *
     * @return \Lucinda\WebSecurity\Request
     */
    public function getResult(): \Lucinda\WebSecurity\Request
    {
        return $this->result;
    }
}
