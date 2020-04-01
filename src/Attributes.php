<?php
namespace Lucinda\Framework;

use \Lucinda\Headers\Wrapper as HeadersWrapper;
use \Lucinda\Logging\Logger;

/**
 * Implements \Lucinda\STDOUT\Attributes specific to Lucinda\Framework
 */
class Attributes extends \Lucinda\STDOUT\Attributes
{
    private $headers;
    private $logger;
    private $userID;
    private $csrfToken;
    private $accessToken;
    
    /**
     * Sets pointer to query HTTP headers with
     *
     * @param HeadersWrapper $wrapper
     */
    public function setHeaders(HeadersWrapper $wrapper): void
    {
        $this->headers = $wrapper;
    }
    
    /**
     * Gets pointer to query HTTP headers with
     *
     * @return HeadersWrapper|NULL
     */
    public function getHeaders(): ?HeadersWrapper
    {
        return $this->headers;
    }
    
    /**
     * Sets pointer to log messages with
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
    
    /**
     * Gets pointer to log messages with
     *
     * @return Logger|NULL
     */
    public function getLogger(): ?Logger
    {
        return $this->logger;
    }
    
    /**
     * Sets logged in user id
     *
     * @param string|integer $userID
     */
    public function setUserId($userID): void
    {
        $this->userID = $userID;
    }
    
    /**
     * Gets logged in user id
     *
     * @return string|integer
     */
    public function getUserId()
    {
        return $this->userID;
    }
    
    /**
     * Sets token to sign logins with in order to fight Cross-Site-Requests-Forgery
     *
     * @param string $token
     */
    public function setCsrfToken(string $token): void
    {
        $this->csrfToken = $token;
    }
    
    /**
     * Gets token to sign logins with in order to fight Cross-Site-Requests-Forgery
     *
     * @return string|NULL
     */
    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }
    
    /**
     * Sets token to be presented by client for stateless authentication as header:
     * Authorization Bearer VALUE
     *
     * @param string $token
     */
    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }
    
    /**
     * Gets token to be presented by client for stateless authentication as header:
     * Authorization Bearer VALUE
     *
     * @return string|NULL
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
}
