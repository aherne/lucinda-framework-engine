<?php
class UserAuthorization implements Lucinda\WebSecurity\UserAuthorizationDAO {
    private $userID;
    
    public function isAllowed(Lucinda\WebSecurity\PageAuthorizationDAO $page, $httpRequestMethod)
    {
        return $page->isPublic() || ($this->userID && $page->getID()==1);
    }

    public function setID($userID)
    {
        $this->userID = $userID;
    }

    public function getID()
    {
        return $this->userID;
    }    
}