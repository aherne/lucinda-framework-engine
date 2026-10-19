<?php

namespace Lucinda\Framework;

/**
 * Stores application service objects under process-wide identifiers
 *
 * Registering an existing identifier replaces its object. Services remain in
 * the static registry until they are individually removed, the registry is
 * cleared, or the PHP process ends.
 */
final class ServiceRegistry
{
    /**
     * @var array<string,object> Registered service objects keyed by identifier
     */
    private static array $services = [];

    /**
     * Registers or replaces a service under an identifier
     *
     * @param string $id Identifier used for subsequent lookups
     * @param object $service Service instance to store
     */
    public static function set(string $id, object $service): void
    {
        self::$services[$id] = $service;
    }

    /**
     * Gets the service registered under an identifier
     *
     * @param string $id Identifier to look up
     * @return object Registered service instance
     * @throws \RuntimeException If the identifier has no registered service
     */
    public static function get(string $id): object
    {
        if (!isset(self::$services[$id])) {
            throw new \RuntimeException("Service not registered: ".$id);
        }
        return self::$services[$id];
    }

    /**
     * Checks whether an identifier has a registered service
     *
     * @param string $id Identifier to look up
     * @return bool True when a service is registered under the identifier
     */
    public static function has(string $id): bool
    {
        return isset(self::$services[$id]);
    }

    /**
     * Removes one registered service or clears the entire registry
     *
     * @param string|null $id Identifier to remove, or null to remove every service
     */
    public static function clear(?string $id = null): void
    {
        if ($id !== null) {
            unset(self::$services[$id]);
            return;
        }
        self::$services = [];
    }
}
