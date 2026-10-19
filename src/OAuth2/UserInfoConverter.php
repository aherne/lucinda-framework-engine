<?php

namespace Lucinda\Framework\OAuth2;

use Lucinda\OAuth2\UserInfo as SourceUserInfo;
use Lucinda\WebSecurity\DAO\OAuth2\UserInformation as DestinationUserInfo;
use Override;

/**
 * Exposes OAuth2 client user data through the web-security DAO contract
 *
 * The adapter retains the source value object and forwards identity fields
 * without copying or normalizing them.
 *
 * @see DestinationUserInfo
 */
final class UserInfoConverter implements DestinationUserInfo
{
    /**
     * Provider user information returned by the OAuth2 client
     */
    private SourceUserInfo $userInfo;

    /**
     * Wraps provider user information for the security workflow
     *
     * @param SourceUserInfo $info Provider identity to expose
     */
    public function __construct(SourceUserInfo $info)
    {
        $this->userInfo = $info;
    }

    /**
     * Gets the identifier assigned by the OAuth2 provider
     *
     * @return int|string Remote user ID forwarded by the OAuth2 client
     */
    #[Override]
    public function getId(): int|string
    {
        return $this->userInfo->getId();
    }

    /**
     * Gets the display name supplied by the OAuth2 provider
     *
     * @return string Remote display name forwarded by the OAuth2 client
     */
    #[Override]
    public function getName(): string
    {
        return $this->userInfo->getName();
    }

    /**
     * Gets the email address supplied by the OAuth2 provider
     *
     * @return string Remote email address forwarded by the OAuth2 client
     */
    #[Override]
    public function getEmail(): string
    {
        return $this->userInfo->getEmail();
    }
}
