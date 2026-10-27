<?php

namespace Lucinda\Framework;

use Lucinda\Headers\Cacheable;
use Lucinda\STDOUT\Request;

/**
 * Provides a request-aware base for computing HTTP cache validators
 *
 * Construction gives subclasses access to the current request and response,
 * then asks them to populate the Last-Modified timestamp and ETag exposed
 * through the Cacheable contract.
 *
 * @see Cacheable
 */
abstract class AbstractCacheable implements Cacheable
{
    /**
     * Current HTTP request whose resource is being described
     */
    protected Request $request;

    /**
     * Response associated with the requested resource
     */
    protected string $response;

    /**
     * Entity tag computed by the subclass, or an empty string when unavailable
     */
    protected string $etag = "";

    /**
     * Resource modification time computed by the subclass, as a Unix timestamp
     */
    protected int $lastModifiedTime = 0;

    /**
     * Captures the HTTP exchange and computes both cache validators
     *
     * setTime() is called before setEtag(), allowing an ETag implementation to
     * incorporate the resolved modification time if needed.
     *
     * @param Request $request Current request for the cacheable resource
     * @param string $response Response whose representation is being validated
     */
    final public function __construct(Request $request, string $response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->setTime();
        $this->setEtag();
    }

    /**
     * Computes and stores the requested resource's modification time
     *
     * Implementations assign the resulting Unix timestamp to
     * $lastModifiedTime. Leaving its default value disables a meaningful
     * Last-Modified comparison.
     */
    abstract protected function setTime(): void;

    /**
     * Gets the computed modification time for the requested resource
     *
     * @return int Unix timestamp populated by setTime()
     */
    public function getTime(): int
    {
        return $this->lastModifiedTime;
    }

    /**
     * Computes and stores the entity tag for the requested resource
     *
     * Implementations assign the validator to $etag. Leaving its default value
     * disables a meaningful ETag comparison.
     */
    abstract protected function setEtag(): void;

    /**
     * Gets the computed entity tag for the requested resource
     *
     * @return string Entity tag populated by setEtag()
     */
    public function getEtag(): string
    {
        return $this->etag;
    }
}
