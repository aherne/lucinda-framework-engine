<?php
namespace Lucinda\Framework;

/**
 * Collects information about logged in GitHub user
 */
class GitHubUserInformation extends AbstractUserInformation
{
    /**
     * Saves logged in user details received from GitHub.
     *
     * @param string[string] $info
     */
    public function __construct($info)
    {
        $this->id = $info["id"];
        $this->name = $info["name"];
        $this->email = (!empty($info["email"])?$info["email"]:"");
    }
}
