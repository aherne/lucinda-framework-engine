<?php
namespace Lucinda\Framework\OAuth2\Instagram;

use Lucinda\Framework\OAuth2\AbstractUserInformation;

/**
 * Collects information about logged in VKontakte user
 */
class UserInformation extends AbstractUserInformation
{
    /**
     * Saves logged in user details received from VKontakte.
     *
     * @param string[string] $info
     */
    public function __construct(array $info)
    {
        $this->id = $info["data"]["id"];
        $this->name = $info["data"]["full_name"];
        $this->email = ""; // driver doesn't send email
    }
}
