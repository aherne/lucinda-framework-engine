<?php
namespace Lucinda\Framework;

/**
 * Binds Lucinda\Headers\Cacheable to Lucinda\STDOUT\Request and Lucinda\MVC\Response
 */
abstract class AbstractCacheable implements \Lucinda\Headers\Cacheable
{
    /**
     * @var \Lucinda\STDOUT\Request
     */
    protected $request;
    
    /**
     * @var \Lucinda\MVC\Response
     */
    protected $response;
    
    /**
     * @var string
     */
    protected $etag = "";
    
    /**
     * @var integer
     */
    protected $last_modified_time = 0;
    
    /**
     *
     * @param \Lucinda\STDOUT\Request $request
     * @param \Lucinda\MVC\Response $response
     */
    public function __construct(\Lucinda\STDOUT\Request $request, \Lucinda\MVC\Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        
        $this->setTime();
        $this->setEtag();
    }
    
    /**
     * Sets value of last modified time of requested resource
     */
    abstract protected function setTime(): void;
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\Headers\Cacheable::getTime()
     */
    public function getTime(): int
    {
        return $this->last_modified_time;
    }
    
    /**
     * Sets value of etag matching requested resource
     */
    abstract protected function setEtag(): void;
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\Headers\Cacheable::getEtag()
     */
    public function getEtag(): string
    {
        return $this->etag;
    }
}
