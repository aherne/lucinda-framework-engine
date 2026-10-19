<?php

namespace Lucinda\Framework;

use Lucinda\ConsoleSTDOUT\Request as ConsoleRequest;
use Lucinda\Logging\RequestInformation;
use Lucinda\STDOUT\Request as WebRequest;

/**
 * Builds logging metadata from the active web or console request
 *
 * Web request data is preferred and includes the route, user agent, and client
 * IP address. If web request construction fails, the class falls back to the
 * current console route. The resulting transport object is created once during
 * construction.
 *
 * @see RequestInformation
 */
final class LoggingRequestInformation
{
    /**
     * Normalized request metadata assembled during construction
     */
    private RequestInformation $requestInfo;

    /**
     * Detects the current execution context and builds its logging metadata
     */
    public function __construct()
    {
        $this->setRequestInformation();
    }

    /**
     * Populates logging metadata from a web request or console fallback
     *
     * Any exception raised while constructing or reading the web request causes
     * the console request route to be used instead.
     */
    private function setRequestInformation()
    {
        $this->requestInfo = new RequestInformation();
        try {
            $request = new WebRequest();
            $this->requestInfo->setUri($request->getURI()->getPage());
            $this->requestInfo->setUserAgent((string) $request->headers("User-Agent"));
            $this->requestInfo->setIpAddress($request->getClient()->getIP());
        } catch (\Exception) {
            $request = new ConsoleRequest();
            $this->requestInfo->setUri($request->getRoute());
        }
    }

    /**
     * Gets the normalized request metadata built during construction
     *
     * @return RequestInformation Logging context for the detected request type
     */
    public function getRequestInformation(): RequestInformation
    {
        return $this->requestInfo;
    }
}
