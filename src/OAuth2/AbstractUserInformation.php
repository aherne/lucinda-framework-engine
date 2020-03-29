<?php
namespace Lucinda\Framework\OAuth2;

use Lucinda\WebSecurity\Authentication\OAuth2\UserInformation;

/**
 * Encapsulates abstract information about remote logged in user on OAuth2 provider.
 */
abstract class AbstractUserInformation implements UserInformation
{
    protected $id;
    protected $name;
    protected $email;
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\UserInformation::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\UserInformation::getEmail()
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     * @see \Lucinda\WebSecurity\Authentication\OAuth2\UserInformation::getId()
     */
    public function getId()
    {
        return $this->id;
    }
}

