<?php

namespace Test\Lucinda\Framework\mocks\OAuth2;

/** Captures native cookie operations only while a State test is running. */
final class BrowserProofCookies
{
    public static bool $enabled = false;
    public static bool $headersSent = false;
    public static bool $writeSucceeds = true;
    public static array $writes = [];
}

namespace Lucinda\Framework\OAuth2;

function headers_sent(): bool
{
    return \Test\Lucinda\Framework\mocks\OAuth2\BrowserProofCookies::$enabled
        ? \Test\Lucinda\Framework\mocks\OAuth2\BrowserProofCookies::$headersSent
        : \headers_sent();
}

function setcookie(string $name, string $value, array $options): bool
{
    if (!\Test\Lucinda\Framework\mocks\OAuth2\BrowserProofCookies::$enabled) {
        return \setcookie($name, $value, $options);
    }
    \Test\Lucinda\Framework\mocks\OAuth2\BrowserProofCookies::$writes[] = [$name, $value, $options];
    return \Test\Lucinda\Framework\mocks\OAuth2\BrowserProofCookies::$writeSucceeds;
}
