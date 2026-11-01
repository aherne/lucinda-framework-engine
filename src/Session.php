<?php

namespace Lucinda\Framework;

use Lucinda\STDOUT\Session as MvcSession;
use Lucinda\WebSecurity\Session as SecuritySession;

/**
 * Adapts MVC session operations to the web-security session contract
 *
 * The caller owns configuration and startup. This adapter shares the injected
 * MVC session, preserves application data during identifier rotation, and
 * invalidates the old identifier. Clearing leaves the session active.
 *
 * @see MvcSession
 * @see SecuritySession
 */
final class Session implements SecuritySession
{
    /**
     * Captures the session already configured by the MVC runtime
     *
     * @param MvcSession $session Shared MVC session, started before security runs
     */
    public function __construct(private MvcSession $session)
    {
    }

    /**
     * Checks whether the shared MVC session is active
     *
     * @return bool True when PHP reports an active session
     */
    public function isStarted(): bool
    {
        return $this->session->isStarted();
    }

    /**
     * Reads an entry while treating missing keys as null
     *
     * @param string $key Session entry name
     * @return mixed Stored value, or null when absent
     */
    public function get(string $key): mixed
    {
        return $this->session->contains($key) ? $this->session->get($key) : null;
    }

    /**
     * Creates or replaces an entry in the shared session
     *
     * @param string $key Session entry name
     * @param mixed $value Value to store
     */
    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    /**
     * Rotates the identifier, retaining current data and deleting old storage
     *
     * @return bool True when PHP regenerates the identifier successfully
     */
    public function regenerateID(): bool
    {
        return $this->session->cookie()->regenerateID(true);
    }

    /**
     * Removes all session entries without closing the shared session
     *
     * Identifier rotation remains a separate operation.
     */
    public function clear(): void
    {
        $this->session->clear();
    }
}
