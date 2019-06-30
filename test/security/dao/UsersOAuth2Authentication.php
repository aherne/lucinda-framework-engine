<?php
class UsersOAuth2Authentication implements Lucinda\WebSecurity\OAuth2AuthenticationDAO, Lucinda\Framework\OAuth2ResourcesDAO {
    static $LOGGED_IN = false;
    public function login(Lucinda\WebSecurity\OAuth2UserInformation $userInformation, $accessToken, $createIfNotExists=true)  {
        self::$LOGGED_IN = true;
        return 1;
    }
    
    public function logout($userID) {
        self::$LOGGED_IN = false;
    }    
    
    public function getDriverName($userID) {
        return "Facebook";
    }
    
    public function getAccessToken($userID) {
        return null;
    }
}