<?php

namespace Lucinda\Framework;

use Lucinda\Headers\Cacheable;
use Lucinda\MVC\Response;
use Lucinda\STDOUT\Request;

/**
 * Binds Lucinda\Headers\Cacheable to Lucinda\STDOUT\Request and Lucinda\MVC\Response
 */
abstract class AbstractCacheable implements Cacheable
{
    protected Request $request;
    protected Response $response;
    protected string $etag = "";
    protected int $lastModifiedTime = 0;

    /**
     *
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
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
     * @see Cacheable::getTime()
     */
    public function getTime(): int
    {
        return $this->lastModifiedTime;
    }

    /**
     * Sets value of etag matching requested resource
     */
    abstract protected function setEtag(): void;

    /**
     * {@inheritDoc}
     * @see Cacheable::getEtag()
     */
    public function getEtag(): string
    {
        return $this->etag;
    }
}
