<?php
namespace Lucinda\Framework;

use Lucinda\STDOUT\Request;
use Lucinda\WebSecurity\Request as WebSecurityRequest;

/**
 * Binds Lucinda\STDOUT\Request to Lucinda\WebSecurity\Request
 */
class RequestBinder
{
    private WebSecurityRequest $result;
    
    /**
     * Kick-starts binding process based on arguments
     *
     * @param Request $request
     * @param string $validPage
     * @param bool $headerIpDetection
     */
    public function __construct(Request $request, string $validPage, bool $headerIpDetection)
    {
        $accessToken = $this->getAccessToken($request);
        $this->setResult($request, $validPage, $accessToken, $headerIpDetection);
    }
    
    /**
     * Gets access token based on Authorization request header received from client. Eg:
     * Authorization Bearer asdadasdasdasdasdasdasd
     *
     * @param Request $request
     * @return string
     */
    private function getAccessToken(Request $request): string
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
     * @param Request $request
     * @param string $validPage
     * @param string $accessToken
     * @param bool $headerIpDetection
     */
    private function setResult(Request $request, string $validPage, string $accessToken, bool $headerIpDetection): void
    {
        $requestBound = new WebSecurityRequest();
        $requestBound->setUri($validPage);
        $requestBound->setMethod($request->getMethod()->value);
        $requestBound->setParameters($request->parameters());
        $requestBound->setContextPath($request->getURI()->getContextPath());
        $requestBound->setAccessToken($accessToken);
        if ($headerIpDetection) {
            $ipDetector = new IPDetector($request);
            $requestBound->setIpAddress($ipDetector->getIP());
        } else {
            $requestBound->setIpAddress($request->getClient()->getIP());
        }
        $this->result = $requestBound;
    }
    
    /**
     * Gets binding result
     *
     * @return WebSecurityRequest
     */
    public function getResult(): WebSecurityRequest
    {
        return $this->result;
    }
}
