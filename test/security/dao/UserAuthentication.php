<?php
class UserAuthentication implements Lucinda\WebSecurity\UserAuthenticationDAO {
    public function logout($userID)
    {}

    public function login($username, $password)
    {
        return ($username=="lucian" && $password=="popescu"?1:0);
    }
}