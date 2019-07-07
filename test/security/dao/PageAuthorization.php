<?php
class PageAuthorization extends Lucinda\WebSecurity\PageAuthorizationDAO {
    const PAGE_IDS = array(
        array("id"=>1, "path"=>"index", "public"=>0),
        array("id"=>2, "path"=>"login", "public"=>1),
        array("id"=>3, "path"=>"logout", "public"=>1),
        array("id"=>4, "path"=>"private", "public"=>0)
    );
    
    public function isPublic()
    {
        foreach(self::PAGE_IDS as $info) {
            if($info["id"]==$this->pageID) {
                return $info["public"];
            }
        }
    }
    
    protected function detectID($path)
    {
        foreach(self::PAGE_IDS as $info) {
            if($info["path"]==$path) {
                return $info["id"];
            }
        }
    }

    
}