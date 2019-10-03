<?php
namespace Lucinda\Framework;

/**
 * Encapsulates login request data. Inner class of FormRequestValidator!
 */
class LoginRequest
{
    private $sourcePage;
    private $targetPage;
    private $username;
    private $password;
    private $rememberMe;
    
    /**
     * Sets value of user name sent in login attempt.
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
    
    /**
     * Sets value of user password sent in login attempt.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password= $password;
    }
    
    /**
     * Sets value of remember me option sent in login attempt (or null, if application doesn't support remember me)
     *
     * @param boolean $rememberMe
     */
    public function setRememberMe($rememberMe)
    {
        $this->rememberMe= $rememberMe;
    }
    
    /**
     * Sets current page.
     *
     * @param string $sourcePage
     */
    public function setSourcePage($sourcePage)
    {
        $this->sourcePage= $sourcePage;
    }
    
    /**
     * Sets page to redirect to on login/logout success/failure.
     *
     * @param string $targetPage
     */
    public function setDestinationPage($targetPage)
    {
        $this->targetPage= $targetPage;
    }
    
    /**
     * Gets value of user name sent in login attempt.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Gets value of user password sent in login attempt.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Gets value of remember me option sent in login attempt (or null, if application doesn't support remember me)
     *
     * @return boolean|null
     */
    public function getRememberMe()
    {
        return $this->rememberMe;
    }
    
    /**
     * Gets current page.
     *
     * @return string
     */
    public function getSourcePage()
    {
        return $this->sourcePage;
    }
    
    /**
     * Gets page to redirect to on login/logout success/failure.
     *
     * @return string
     */
    public function getDestinationPage()
    {
        return $this->targetPage;
    }
}
