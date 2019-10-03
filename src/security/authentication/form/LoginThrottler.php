<?php
namespace Lucinda\Framework;

/**
 * Defines blueprints of a login throttler, able to guard against BruteForce login attempts
 */
abstract class LoginThrottler
{    
    /**
     * Detects client throttling state based on arguments provided. 
     * 
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @param string $ipAddress Client ip address resolved from headers
     * @param string $userName Username client has attempted
     */
    abstract public function __construct(\Lucinda\MVC\STDOUT\Request $request, $ipAddress, $userName);
    
    /**
     * Gets number of seconds client will be banned from authenticating
     * 
     * @return integer|null 
     */
    abstract public function getTimePenalty();
    
    /**
     * Marks subsequent login as failed, making client liable for time penalties
     */
    abstract public function setFailure();
    
    /**
     * Marks subsequent login as successful, removing any previous failures and penalties
     */
    abstract public function setSuccess();
}