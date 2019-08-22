<?php
namespace Lucinda\Framework;

/**
 * Encapsulates logout request data. Inner class of FormRequestValidator!
 */
class LogoutRequest
{
    private $sourcePage;
    private $targetPage;
    
    /**
     * Sets current page.
     *
     * @param string $sourcePage
     */
    public function setSourcePage($sourcePage)
    {
        $this->sourcePage= $sourcePage;
    }
    
    /**
     * Sets page to redirect to on login/logout success/failure.
     *
     * @param string $targetPage
     */
    public function setDestinationPage($targetPage)
    {
        $this->targetPage= $targetPage;
    }
    
    /**
     * Gets current page.
     *
     * @return string
     */
    public function getSourcePage()
    {
        return $this->sourcePage;
    }
    
    /**
     * Gets page to redirect to on login/logout success/failure.
     *
     * @return string
     */
    public function getDestinationPage()
    {
        return $this->targetPage;
    }
}
