<?php
namespace Lucinda\Framework\OAuth2;

use Lucinda\WebSecurity\Authentication\OAuth2\UserInformation;

/**
 * Encapsulates abstract information about remote logged in user on OAuth2 provider.
 */
abstract class AbstractUserInformation implements UserInformation
{
    protected string|int $id;
    protected string $name;
    protected string $email;
    
    /**
     * {@inheritDoc}
     * @see UserInformation::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     * @see UserInformation::getEmail()
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     * @see UserInformation::getId()
     */
    public function getId(): int|string
    {
        return $this->id;
    }
}
