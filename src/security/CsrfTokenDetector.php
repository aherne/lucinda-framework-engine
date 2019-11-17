<?php
namespace Lucinda\Framework;

require("vendor/lucinda/security/src/token/SynchronizerToken.php");

/**
 * Binds SynchronizerToken @ SECURITY-API with settings from configuration.xml @ SERVLETS-API  then sets up an object based on which one can perform
 * CSRF checks later on in application's lifecycle.
 */
class CsrfTokenDetector
{
    const DEFAULT_EXPIRATION = 10*60;
    
    private $secret;
    private $expiration;
    
    private $token;
    
    /**
     * Creates an object
     *
     * @param \SimpleXMLElement $xml Contents of security.csrf @ configuration.xml
     * @param string $ipAddress Client ip address resolved from headers
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    public function __construct(\SimpleXMLElement $xml, $ipAddress)
    {
        $xml = $xml->csrf;
        if (empty($xml)) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'csrf' child of 'security' tag is empty or missing");
        }
        
        // sets secret
        $secret = (string) $xml["secret"];
        if (!$secret) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'secret' is mandatory for 'csrf' tag");
        }
        
        // sets token
        $this->token = new \Lucinda\WebSecurity\SynchronizerToken($ipAddress, $secret);
        
        // sets expiration
        $expiration = (string) $xml["expiration"];
        if (!$expiration) {
            $expiration = self::DEFAULT_EXPIRATION;
        }
        $this->expiration = $expiration;
    }
    
    /**
     * Encodes a token based on unique user identifier
     * @param mixed $userID Unique user identifier (usually an integer)
     * @return string Value of synchronizer token.
     * @throws \Lucinda\WebSecurity\EncryptionException If encryption of token fails.
     */
    public function generate($userID)
    {
        return $this->token->encode($userID, $this->expiration);
    }
    
    /**
     * Checks if a token is valid for specific uuid.
     *
     * @param string $token Value of synchronizer token
     * @param mixed $userID Unique user identifier (usually an integer)
     * @return boolean
     * @throws \Lucinda\WebSecurity\EncryptionException If decryption of token fails.
     * @throws \Lucinda\WebSecurity\TokenException If token fails validations.
     * @throws \Lucinda\WebSecurity\TokenRegenerationException If token needs to be refreshed
     */
    public function isValid($token, $userID)
    {
        try {
            $tokenUserID = $this->token->decode($token);
            if ($tokenUserID == $userID) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
