<?php
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
	 * @param SimpleXMLElement $xml Contents of security.authorization.by_dao tag @ configuration.xml
	 * @param string $currentPage Current page requested.
	 * @param mixed $userID Unique user identifier (usually an integer) 
	 * @throws SQLConnectionException If connection to database server fails.
	 * @throws SQLStatementException If query to database server fails.
	 */
	public function __construct(SimpleXMLElement $xml, $currentPage, $userID) {
		// create dao object
		$xmlTag = $xml->security->authorization->by_dao;
		
		// detects logged in callback to use if authorization fails
		$loggedInCallback = (string) $xmlTag["logged_in_callback"];
		if(!$loggedInCallback) $loggedInCallback = self::DEFAULT_LOGGED_IN_PAGE;
		
		// detects logged out callback to use if authorization fails
		$loggedOutCallback = (string) $xmlTag["logged_out_callback"];
		if(!$loggedOutCallback) $loggedOutCallback = self::DEFAULT_LOGGED_OUT_PAGE;
		
		// loads and instances page DAO object
		$className = (string) $xmlTag["page_dao"];
		load_class((string) $xml->application->paths->dao, $className);
		$pageDAO = new $className();
		if(!($pageDAO instanceof PageAuthorizationDAO)) throw new ServletException("Class must be instance of PageAuthorizationDAO!");
		$pageDAO->setID($currentPage);
		
		// loads and instances user DAO object
		$className = (string) $xmlTag["user_dao"];
		load_class((string) $xml->application->paths->dao, $className);
		$userDAO = new $className();
		if(!($userDAO instanceof UserAuthorizationDAO)) throw new ServletException("Class must be instance of UserAuthorizationDAO!");
		$userDAO->setID($userID);
		
		// performs authorization
		$authorization = new DAOAuthorization($loggedInCallback, $loggedOutCallback);
		$this->setResult($authorization->authorize($pageDAO, $userDAO, $_SERVER["REQUEST_METHOD"]));
	}
}
