<?php

namespace Lucinda\Framework\OAuth2\VK;

use Lucinda\Framework\OAuth2\AbstractUserInformation;

/**
 * Collects information about logged in VKontakte user
 */
class UserInformation extends AbstractUserInformation
{
    /**
     * Saves logged in user details received from VKontakte.
     *
     * @param array<string, array<int, array<string, string>>> $info
     */
    public function __construct(array $info)
    {
        $this->id = $info["response"][0]["uid"];
        $this->name = $info["response"][0]["first_name"]." ".$info["response"][0]["last_name"];
        $this->email = ""; // driver doesn't send email
    }
}
