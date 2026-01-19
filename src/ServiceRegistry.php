<?php

namespace Lucinda\Framework;

/**
 * Minimal static registry for framework services.
 */
final class ServiceRegistry
{
    /**
     * @var array<string,object>
     */
    private static array $services = [];

    /**
     * Registers a service instance by identifier.
     *
     * @param string $id
     * @param object $service
     */
    public static function set(string $id, object $service): void
    {
        self::$services[$id] = $service;
    }

    /**
     * Gets a service instance by identifier.
     *
     * @param string $id
     * @return object
     */
    public static function get(string $id): object
    {
        if (!isset(self::$services[$id])) {
            throw new \RuntimeException("Service not registered: ".$id);
        }
        return self::$services[$id];
    }

    /**
     * Checks if a service is registered.
     *
     * @param string $id
     * @return bool
     */
    public static function has(string $id): bool
    {
        return isset(self::$services[$id]);
    }

    /**
     * Clears one service or the whole registry.
     *
     * @param string|null $id
     * @return void
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
