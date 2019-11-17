<?php
namespace Lucinda\Framework;

require("LoginRequest.php");
require("LogoutRequest.php");

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
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->xml = $xml->authentication->form;
    }
    
    /**
     * Sets up login data, if operation was requested
     *
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @throws \Lucinda\WebSecurity\AuthenticationException If POST parameters are not provided when logging in.
     * @return LoginRequest|null
     */
    public function login(\Lucinda\MVC\STDOUT\Request $request)
    {
        $currentPage = $request->getValidator()->getPage();
        
        $loginRequest = new LoginRequest();
        
        // set source page;
        $sourcePage = (string) $this->xml->login["page"];
        if (!$sourcePage) {
            $sourcePage = self::DEFAULT_LOGIN_PAGE;
        }
        if ($sourcePage != $currentPage || $request->getMethod()!="POST") {
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
        $username = $request->parameters($parameterUsername);
        if (!$username) {
            throw new  \Lucinda\WebSecurity\AuthenticationException("POST parameter missing: ".$parameterUsername);
        }
        $loginRequest->setUsername($username);
        
        $password = $request->parameters($parameterPassword);
        if (!$password) {
            throw new  \Lucinda\WebSecurity\AuthenticationException("POST parameter missing: ".$parameterPassword);
        }
        $loginRequest->setPassword($password);
        
        $loginRequest->setRememberMe($request->parameters($parameterRememberMe)?true:false);
        
        return $loginRequest;
    }
    
    /**
     * Sets up logout data, if operation was requested
     *
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulated client request data.
     * @return LogoutRequest|null
     */
    public function logout(\Lucinda\MVC\STDOUT\Request $request)
    {
        $currentPage = $request->getValidator()->getPage();
        
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
