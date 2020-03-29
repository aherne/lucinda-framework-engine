<?php
namespace Lucinda\Framework;

/**
 * Repository of singleton objects, to be used by quick procedural functions provided by framework
 */
class SingletonRepository
{
    private static $instances = [];
    
    /**
     * Sets object by unique identifier
     * 
     * @param string $key
     * @param object $object
     */
    public static function set(string $key, $object)
    {
        self::$instances[$key] = $object;
    }
    
    /**
     * Gets object by unique identifier
     * 
     * @param string $key
     * @return object
     */
    public static function get(string $key)
    {
        return self::$instances[$key];
    }
}