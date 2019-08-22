<?php
class UserAuthentication implements Lucinda\WebSecurity\UserAuthenticationDAO, Lucinda\WebSecurity\UserAuthorizationRoles
{
    public function logout($userID)
    {
    }

    public function login($username, $password)
    {
        return ($username=="lucian" && $password=="popescu"?1:0);
    }
    
    public function getRoles($userID)
    {
        return $userID?["USER"]:["GUEST"];
    }
}
