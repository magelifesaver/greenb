<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
/**
 * Simple container implementation for storing object instances.
 *
 * @since 1.0.0
 *
 * @phpstan-template T
 */
final class Container
{
    /** @var array<string, object> the container for storing instances */
    private static array $instances = [];
    /**
     * Add an instance to the container.
     *
     * @since 1.0.0
     *
     * @param string $class
     * @param object|null $instance
     *
     * @phpstan-param class-string<T> $class
     * @phpstan-param T|null $instance
     */
    public static function set(string $class, $instance): void
    {
        self::$instances[$class] = $instance;
    }
    /**
     * Gets an instance from the container.
     *
     * @since 1.0.0
     *
     * @param string $class
     * @return object|null
     *
     * @phpstan-param class-string<T> $class
     *
     * @phpstan-return T|null
     */
    public static function get(string $class)
    {
        return self::$instances[$class] ?? null;
    }
    /**
     * Checks if an instance exists in the container.
     *
     * @since 1.0.0
     *
     * @param string $class
     *
     * @phpstan-param class-string<T> $class
     *
     * @return bool
     */
    public static function has(string $class): bool
    {
        return isset(self::$instances[$class]);
    }
}
