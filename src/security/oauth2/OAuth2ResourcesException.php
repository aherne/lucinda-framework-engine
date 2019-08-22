<?php
namespace Lucinda\Framework;

/**
 * Exception thrown on attempts to retrieve resources from an OAuth2 provider without an access token
 */
class OAuth2ResourcesException extends \Exception
{
}
