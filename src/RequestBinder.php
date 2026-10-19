<?php

namespace Lucinda\Framework;

use Lucinda\STDOUT\Request;
use Lucinda\WebSecurity\Request as WebSecurityRequest;

/**
 * Adapts an MVC HTTP request for the web-security workflow
 *
 * The adapter copies the resolved route, method, parameters, context path, and
 * client IP into a provider-neutral security request. It also extracts a Bearer
 * token from the Authorization header when present.
 *
 * @see WebSecurityRequest
 */
final class RequestBinder
{
    /**
     * Security request populated during construction
     */
    private WebSecurityRequest $result;

    /**
     * Builds a security request from the current MVC request
     *
     * @param Request $request Current HTTP request to normalize
     * @param string $validPage Resolved application route used by security configuration
     */
    public function __construct(Request $request, string $validPage)
    {
        $accessToken = $this->getAccessToken($request);
        $this->setResult($request, $validPage, $accessToken);
    }

    /**
     * Extracts a Bearer token from the Authorization request header
     *
     * The scheme match is case-insensitive and surrounding whitespace is
     * removed from the token. Other schemes and missing headers produce an
     * empty string.
     *
     * @param Request $request HTTP request containing the authorization header
     * @return string Token without the `Bearer` prefix, or an empty string when absent
     */
    private function getAccessToken(Request $request): string
    {
        $accessToken = "";
        $header = $request->headers("Authorization");
        if ($header && stripos($header, "Bearer ")===0) {
            $accessToken = trim(substr($header, 7));
        }
        return $accessToken;
    }

    /**
     * Copies normalized HTTP request values into a security request
     *
     * @param Request $request Source MVC request
     * @param string $validPage Resolved route used for security matching
     * @param string $accessToken Extracted bearer token, or an empty string
     */
    private function setResult(Request $request, string $validPage, string $accessToken): void
    {
        $requestBound = new WebSecurityRequest();
        $requestBound->setUri($validPage);
        $requestBound->setMethod($request->getMethod()->value);
        $requestBound->setParameters($request->parameters());
        $requestBound->setContextPath($request->getURI()->getContextPath());
        $requestBound->setAccessToken($accessToken);
        $requestBound->setIpAddress($request->getClient()->getIP());
        $this->result = $requestBound;
    }

    /**
     * Gets the security request built during construction
     *
     * @return WebSecurityRequest Normalized request ready for the security workflow
     */
    public function getResult(): WebSecurityRequest
    {
        return $this->result;
    }
}
