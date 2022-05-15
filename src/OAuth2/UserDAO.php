<?php

namespace Lucinda\Framework\OAuth2;

/**
 * Interface implementing blueprints for detecting current OAuth2 provider info from DB for current logged in user
 */
interface UserDAO
{
    /**
     * Gets current access token from DB for current logged in user
     *
     * @param int|string $userID
     * @return string|NULL
     */
    public function getAccessToken(int|string $userID): ?string;

    /**
     * Gets name of OAuth2 vendor from DB for current logged in user
     *
     * @param int|string $userID
     * @return string|NULL
     */
    public function getVendor(int|string $userID): ?string;
}
