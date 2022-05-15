<?php

namespace Lucinda\Framework\OAuth2\Yandex;

use Lucinda\Framework\OAuth2\AbstractUserInformation;

/**
 * Collects information about logged in Yandex user
 */
class UserInformation extends AbstractUserInformation
{
    /**
     * Saves logged in user details received from Yandex.
     *
     * @param array<string, string> $info
     */
    public function __construct(array $info)
    {
        $this->id = $info["id"];
        $this->name = $info["first_name"]." ".$info["last_name"];
        $this->email = (!empty($info["default_email"]) ? $info["default_email"] : "");
    }
}
