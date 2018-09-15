<?php
class TestCacheable extends Lucinda\Framework\CacheableDriver {
    protected function setTime() {
        return time();
    }

    protected function setEtag() {
        return "asd";
    }
}