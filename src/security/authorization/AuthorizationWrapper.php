<?php
namespace Lucinda\Framework;

/**
 * Defines an abstract authorization mechanism that works with AuthenticationResult
 */
abstract class AuthorizationWrapper
{
    private $result;
    
    /**
     * Sets result of authorization attempt.
     *
     * @param \Lucinda\WebSecurity\AuthorizationResult $result
     */
    protected function setResult(\Lucinda\WebSecurity\AuthorizationResult $result)
    {
        $this->result = $result;
    }
    
    /**
     * Gets result of authorization attempt
     *
     * @return \Lucinda\WebSecurity\AuthorizationResult
     */
    public function getResult()
    {
        return $this->result;
    }
}
