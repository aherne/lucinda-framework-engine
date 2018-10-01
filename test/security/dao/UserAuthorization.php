<?php
class UserAuthorization extends Lucinda\WebSecurity\UserAuthorizationDAO {
    public function isAllowed(Lucinda\WebSecurity\PageAuthorizationDAO $page, $httpRequestMethod)
    {
        return $page->isPublic() || ($this->userID && $page->getID()==1);
    }
}