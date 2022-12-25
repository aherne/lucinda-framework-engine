<?php

namespace Lucinda\Framework;

use Lucinda\ConsoleSTDOUT\Request as ConsoleRequest;
use Lucinda\Logging\RequestInformation;
use Lucinda\STDOUT\Request as WebRequest;

/**
 * Binds Lucinda\(Console_)STDOUT\Request request to Lucinda\Logging\RequestInformation
 */
class LoggingRequestInformation
{
    private RequestInformation $requestInfo;

    /**
     * Bootstraps page
     */
    public function __construct()
    {
        $this->setRequestInformation();
    }

    /**
     * Performs binding
     *
     * @return void
     */
    private function setRequestInformation()
    {
        $this->requestInfo = new RequestInformation();
        try {
            $request = new WebRequest();
            $this->requestInfo->setUri($request->getURI()->getPage());
            $this->requestInfo->setUserAgent($request->headers("User-Agent"));
            $this->requestInfo->setIpAddress($request->getClient()->getIP());
        } catch (\Exception) {
            $request = new ConsoleRequest();
            $this->requestInfo->setUri($request->getRoute());
        }
    }

    /**
     * Gets request information
     *
     * @return RequestInformation
     */
    public function getRequestInformation(): RequestInformation
    {
        return $this->requestInfo;
    }
}
