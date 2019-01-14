<?php
namespace Lucinda\Framework;
/**
 * Driver binding Servlets API and HTTP Caching API, fed with application and request information. Children have the responsibility
 * of implementing setters for etag and last modified time according to their specific business needs.
 */
abstract class CacheableDriver implements \Lucinda\Caching\Cacheable {
	/**
	 * @var \Lucinda\MVC\STDOUT\Application
	 */
	protected $application;
	/**
	 * @var \Lucinda\MVC\STDOUT\Request
	 */
	protected $request;
	/**
	 * @var \Lucinda\MVC\STDOUT\Response
	 */
	protected $response;
	
	/**
	 * @var string
	 */
	protected $etag;
	
	/**
	 * @var integer
	 */
	protected $last_modified_time;
	
	/**
	 * Saves STDOUT MVC API objects for internal operations and calls children to set ETag and Last-Modified values.
	 * 
	 * @param \Lucinda\MVC\STDOUT\Application $application
	 * @param \Lucinda\MVC\STDOUT\Request $request
	 * @param \Lucinda\MVC\STDOUT\Response $response
	 */
	public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response) {
		$this->application = $application;
		$this->request = $request;
		$this->response = $response;
		
		$this->setTime();
		$this->setEtag();
	}
	
	/**
	 * Sets value of last modified time of requested resource
	 */
	abstract protected function setTime();
	
	/**
	 * {@inheritDoc}
	 * @see \Lucinda\Caching\Cacheable::getTime()
	 */
	public function getTime() {
		return $this->last_modified_time;
	}
	
	/**
	 * Sets value of etag matching requested resource
	 */
	abstract protected function setEtag();
	
	/**
	 * {@inheritDoc}
	 * @see \Lucinda\Caching\Cacheable::getEtag()
	 */
	public function getEtag() {
		return $this->etag;
	}
}