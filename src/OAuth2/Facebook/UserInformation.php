<?php
namespace Lucinda\Framework\OAuth2\Facebook;

use Lucinda\Framework\OAuth2\AbstractUserInformation;

/**
 * Collects information about logged in Facebook user
 */
class UserInformation extends AbstractUserInformation
{
    /**
     * Saves logged in user details received from Facebook.
     *
     * @param string[string] $info
     */
    public function __construct(array $info)
    {
        $this->id = $info["id"];
        $this->name = $info["name"];
        $this->email = (!empty($info["email"])?$info["email"]:"");
    }
}
