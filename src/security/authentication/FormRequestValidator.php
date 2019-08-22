<?php
namespace Lucinda\Framework;

require_once("AuthenticationWrapper.php");
require_once("LoginRequest.php");
require_once("LogoutRequest.php");

/**
 * Validates authentication requests in configuration.xml and encapsulates them into objects
 */
class FormRequestValidator
{
    const DEFAULT_PARAMETER_USERNAME = "username";
    const DEFAULT_PARAMETER_PASSWORD = "password";
    const DEFAULT_PARAMETER_REMEMBER_ME = "remember_me";
    const DEFAULT_TARGET_PAGE = "index";
    const DEFAULT_LOGIN_PAGE = "login";
    const DEFAULT_LOGOUT_PAGE = "logout";
    
    private $xml;
    
    /**
     * Creates an object.
     *
     * @param \SimpleXMLElement $xml Contents of security.authentication.form tag @ configuration.xml.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->xml = $xml->authentication->form;
    }
    
    /**
     * Sets up login data, if operation was requested
     *
     * @throws \Lucinda\MVC\STDOUT\ServletException If request doesn't come with mandatory parameters.
     * @param string $currentPage Current page requested.
     * @return LoginRequest|null
     */
    public function login($currentPage)
    {
        $loginRequest = new LoginRequest();
        
        // set source page;
        $sourcePage = (string) $this->xml->login["page"];
        if (!$sourcePage) {
            $sourcePage = self::DEFAULT_LOGIN_PAGE;
        }
        if ($sourcePage != $currentPage || empty($_POST)) {
            return null;
        }
        $loginRequest->setSourcePage($sourcePage);
        
        // get target page
        $targetPage = (string) $this->xml->login["target"];
        if (!$targetPage) {
            $targetPage = self::DEFAULT_TARGET_PAGE;
        }
        $loginRequest->setDestinationPage($targetPage);
        
        // get parameter names
        $parameterUsername = (string) $this->xml->login["parameter_username"];
        if (!$parameterUsername) {
            $parameterUsername = self::DEFAULT_PARAMETER_USERNAME;
        }
        $parameterPassword = (string) $this->xml->login["parameter_password"];
        if (!$parameterPassword) {
            $parameterPassword = self::DEFAULT_PARAMETER_PASSWORD;
        }
        $parameterRememberMe = (string) $this->xml->login["parameter_rememberMe"];
        if (!$parameterRememberMe) {
            $parameterRememberMe = self::DEFAULT_PARAMETER_REMEMBER_ME;
        }
        
        // get parameter values
        $username = (!empty($_POST[$parameterUsername])?$_POST[$parameterUsername]:"");
        if (!$username) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("POST parameter missing: ".$parameterUsername);
        }
        $loginRequest->setUsername($username);
        
        $password = (!empty($_POST[$parameterPassword])?$_POST[$parameterPassword]:"");
        if (!$password) {
            throw new  \Lucinda\MVC\STDOUT\ServletException("POST parameter missing: ".$parameterPassword);
        }
        $loginRequest->setPassword($password);
        
        $loginRequest->setRememberMe(!empty($_POST[$parameterRememberMe])?true:false);
        
        return $loginRequest;
    }
    
    /**
     * Sets up logout data, if operation was requested
     *
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
     * @param string $currentPage Current page requested.
     * @return LogoutRequest|null
     */
    public function logout($currentPage)
    {
        $logoutRequest = new LogoutRequest();
        
        // set source page
        $sourcePage = (string) $this->xml->logout["page"];
        if (!$sourcePage) {
            $sourcePage = self::DEFAULT_LOGOUT_PAGE;
        }
        if ($sourcePage != $currentPage) {
            return null;
        }
        $logoutRequest->setSourcePage($currentPage);
        
        // set destination page
        $targetPage = (string) $this->xml->logout["target"];
        if (!$targetPage) {
            $targetPage = self::DEFAULT_LOGIN_PAGE;
        }
        $logoutRequest->setDestinationPage($targetPage);
        
        return $logoutRequest;
    }
}
