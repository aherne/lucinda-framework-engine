<?php

namespace Test\Lucinda\Framework\mocks;

use Lucinda\Framework\AbstractCacheable;

final class Cacheable extends AbstractCacheable
{
    protected function setTime(): void
    {
        $this->lastModifiedTime = 1700000000;
    }

    protected function setEtag(): void
    {
        $this->etag = sha1($this->request->getURI()->getPage()."#".$this->lastModifiedTime);
    }
}
