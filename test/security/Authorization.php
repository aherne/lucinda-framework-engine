<?php
set_include_path(dirname(dirname(__DIR__)));
require_once(dirname(dirname(__DIR__))."/src/ClassLoader.php");


$xml = simplexml_load_file("configuration.xml");
$xml = $xml->security;

$_SERVER["REQUEST_METHOD"] = "GET";

/**
 * DAO Authorization
 */
require_once(dirname(dirname(__DIR__))."/src/security/authorization/DAOAuthorizationWrapper.php");

// authorization success
$authorization = new Lucinda\Framework\DAOAuthorizationWrapper($xml, "index", 1);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::OK?"Y":"N")."\n";

// authorization failed due to user not logged in
$authorization = new Lucinda\Framework\DAOAuthorizationWrapper($xml, "index", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::UNAUTHORIZED?"Y":"N")."\n";

// authorization failed due to logged in user not having rights to access page
$authorization = new Lucinda\Framework\DAOAuthorizationWrapper($xml, "private", 1);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::FORBIDDEN?"Y":"N")."\n";

// authorization failed due to page not found in db
$authorization = new Lucinda\Framework\DAOAuthorizationWrapper($xml, "missing", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::NOT_FOUND?"Y":"N")."\n";

/**
 * XML Authorization
 */
require_once(dirname(dirname(__DIR__))."/src/security/authorization/XMLAuthorizationWrapper.php");
require_once(dirname(dirname(__DIR__))."/vendor/lucinda/security/src/authentication/UserAuthenticationDAO.php");
require_once("dao/UserAuthentication.php");

// authorization success
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "index", 1);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::OK?"Y":"N")."\n";

// authorization failed due to user not logged in
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "index", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::UNAUTHORIZED?"Y":"N")."\n";

// authorization failed due to logged in user not having rights to access page
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "private", 1);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::FORBIDDEN?"Y":"N")."\n";

// authorization failed due to page not found in db
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "missing", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::NOT_FOUND?"Y":"N")."\n";

$xml = simplexml_load_string('<xml>
    <routes>
        <route url="login" roles="GUEST,USER"/>
        <route url="login/facebook" roles="GUEST"/>
        <route url="index" roles="USER"/>
        <route url="logout" roles="USER"/>
        <route url="private" roles="ADMINISTRATOR"/>
    </routes>
    <security dao_path="dao">
        <csrf secret="asdf"/>
        <persistence>
            <synchronizer_token secret="rrwe"/>
        </persistence>
        <authentication>
            <form>
                <login/>
                <logout/>
            </form>
        </authentication>
        <authorization>
			<by_route/>
        </authorization>
    </security>
    <users>
    	<user id="1" username="lucian" password="$2y$10$zXPli6TXeksjxwSofLKoluTezu9fydg05wVMhMNkbSIDhY.LTZb2S" roles="USER"/>
    	<user id="2" username="john" password="$2y$10$.8WH6Xq4upytcWifsQk60.3Nxa4jvG9jTNihCbL/8ByXMiugeHr2G" roles="ADMINISTRATOR,USER"/>
    </users>
</xml>');
$xml = $xml->security;

// authorization success
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "index", 1);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::OK?"Y":"N")."\n";

// authorization failed due to user not logged in
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "index", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::UNAUTHORIZED?"Y":"N")."\n";

$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "login", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::OK?"Y":"N")."\n";

// authorization failed due to logged in user not having rights to access page
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "private", 1);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::FORBIDDEN?"Y":"N")."\n";

// authorization failed due to page not found in db
$authorization = new Lucinda\Framework\XMLAuthorizationWrapper($xml, "missing", 0);
echo __LINE__.": ".($authorization->getResult()->getStatus()==\Lucinda\WebSecurity\AuthorizationResultStatus::NOT_FOUND?"Y":"N")."\n";
