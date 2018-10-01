<?php
namespace Lucinda\Framework;

require_once("vendor/lucinda/security/src/authorization/DAOAuthorization.php");
require_once("AuthorizationWrapper.php");
/**
 * Binds DAOAuthorization @ SECURITY-API to settings from configuration.xml @ SERVLETS-API then performs request authorization via database.
 */
class DAOAuthorizationWrapper extends AuthorizationWrapper {
	const DEFAULT_LOGGED_IN_PAGE = "index";
	const DEFAULT_LOGGED_OUT_PAGE = "login";
	const REFRESH_TIME = 0;
	
	/**
	 * Creates an object
	 * 
	 * @param \SimpleXMLElement $xml Contents of security.authorization.by_dao tag @ configuration.xml
	 * @param string $currentPage Current page requested.
	 * @param mixed $userID Unique user identifier (usually an integer) 
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 */
	public function __construct(\SimpleXMLElement $xml, $currentPage, $userID) {
		// create dao object
		$xmlTag = $xml->authorization->by_dao;
		
		// detects logged in callback to use if authorization fails
		$loggedInCallback = (string) $xmlTag["logged_in_callback"];
		if(!$loggedInCallback) $loggedInCallback = self::DEFAULT_LOGGED_IN_PAGE;
		
		// detects logged out callback to use if authorization fails
		$loggedOutCallback = (string) $xmlTag["logged_out_callback"];
		if(!$loggedOutCallback) $loggedOutCallback = self::DEFAULT_LOGGED_OUT_PAGE;
		
		// loads and instances page DAO object
		$className = (string) $xmlTag["page_dao"];
		load_class((string) $xml["dao_path"], $className);
		$pageDAO = new $className($currentPage);
		if(!($pageDAO instanceof \Lucinda\WebSecurity\PageAuthorizationDAO)) throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of PageAuthorizationDAO!");

		// loads and instances user DAO object
		$className = (string) $xmlTag["user_dao"];
		load_class((string) $xml["dao_path"], $className);
		$userDAO = new $className($userID);
		if(!($userDAO instanceof \Lucinda\WebSecurity\UserAuthorizationDAO)) throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of UserAuthorizationDAO!");

		// performs authorization
		$authorization = new \Lucinda\WebSecurity\DAOAuthorization($loggedInCallback, $loggedOutCallback);
		$this->setResult($authorization->authorize($pageDAO, $userDAO, $_SERVER["REQUEST_METHOD"]));
	}
}
