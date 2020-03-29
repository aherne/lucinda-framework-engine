<?php
namespace Lucinda\Framework\OAuth2\LinkedIn;

use Lucinda\Framework\OAuth2\AbstractUserInformation;

/**
 * Collects information about logged in LinkedIn user
 */
class UserInformation extends AbstractUserInformation
{
    /**
     * Saves logged in user details received from LinkedIn.
     *
     * @param string[string] $info
     */
    public function __construct(array $info)
    {
        $this->id = $info["id"];
        $this->name = $info["firstName"]." ".$info["lastName"];
        $this->email = (!empty($info["email"])?$info["email"]:"");
    }
}
