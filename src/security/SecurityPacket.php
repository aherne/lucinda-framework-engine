<?php
namespace Lucinda\Framework;

require("vendor/lucinda/security/src/authentication/AuthenticationResultStatus.php");
require("vendor/lucinda/security/src/authorization/AuthorizationResultStatus.php");

/**
 * Holds information about authentication/authorization outcomes incompatible with continuing execution (requiring a redirection).
 */
class SecurityPacket extends \Exception
{
    private $callback;
    private $status;
    private $accessToken;
    private $timePenalty;
    
    /**
     * Sets path to redirect to.
     *
     * @param string $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }
    
    /**
     * Gets path to redirect to.
     *
     * @return string
     */
    public function getCallback()
    {
        return $this->callback;
    }
    
    /**
     * Sets redirection reason.
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $result = "";
        switch ($status) {
            case \Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_OK:
                $result= "login_ok";
                break;
            case \Lucinda\WebSecurity\AuthenticationResultStatus::LOGOUT_OK:
                $result= "logout_ok";
                break;
            case \Lucinda\WebSecurity\AuthenticationResultStatus::DEFERRED:
                $result= "redirect";
                break;
            case \Lucinda\WebSecurity\AuthenticationResultStatus::LOGIN_FAILED:
                $result= "login_failed";
                break;
            case \Lucinda\WebSecurity\AuthenticationResultStatus::LOGOUT_FAILED:
                $result= "logout_failed";
                break;
            case \Lucinda\WebSecurity\AuthorizationResultStatus::UNAUTHORIZED:
                $result= "unauthorized";
                break;
            case \Lucinda\WebSecurity\AuthorizationResultStatus::FORBIDDEN:
                $result= "forbidden";
                break;
            case \Lucinda\WebSecurity\AuthorizationResultStatus::NOT_FOUND:
                $result= "not_found";
                break;
            default:
                break;
        }
        $this->status = $result;
    }
    
    /**
     * Gets redirection reason.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Sets access token (useful for stateless applications).
     *
     * @param mixed $userID Authenticated user id.
     * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers List of persistence drivers registered.
     */
    public function setAccessToken($userID, $persistenceDrivers)
    {
        $token = "";
        if ($userID) {
            foreach ($persistenceDrivers as $persistenceDriver) {
                if ($persistenceDriver instanceof \Lucinda\WebSecurity\TokenPersistenceDriver) {
                    $token = $persistenceDriver->getAccessToken();
                }
            }
        }
        $this->accessToken = $token;
    }
    
    /**
     * Gets access token. In order to stay authenticated, each request will have to include this as a header.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
    
    /**
     * Sets number of seconds client will be banned from authenticating
     *
     * @param integer $timePenalty
     */
    public function setTimePenalty($timePenalty)
    {
        $this->timePenalty = $timePenalty;
    }
    
    /**
     * Gets number of seconds client will be banned from authenticating
     *
     * @return integer|null
     */
    public function getTimePenalty()
    {
        return $this->timePenalty;
    }
}
