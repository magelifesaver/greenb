<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
/**
 * Provides a singleton pattern for classes.
 *
 * @since 1.0.0
 *
 * @phpstan-consistent-constructor
 */
trait Is_Singleton
{
    /** @var array<class-string, $this> */
    protected static array $instance = [];
    /**
     * Protected constructor.
     *
     * Prevents direct object instantiation.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
    }
    /**
     * Cloning instances is forbidden due to singleton pattern.
     *
     * @since 1.0.0
     */
    final public function __clone()
    {
        // phpcs:ignore
        trigger_error(sprintf('You cannot clone instances of %s.', get_class($this)), \E_USER_NOTICE);
    }
    /**
     * Unserializing instances is forbidden due to singleton pattern.
     *
     * @since 1.0.0
     */
    final public function __wakeup()
    {
        // phpcs:ignore
        trigger_error(sprintf('You cannot unserialize instances of %s.', get_class($this)), \E_USER_NOTICE);
    }
    /**
     * Determines if the current instance is loaded.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    final public static function is_loaded(): bool
    {
        return !empty(static::$instance[static::class]);
    }
    /**
     * Gets the implementing class instance.
     *
     * Ensures only one instance can be loaded.
     *
     * @since 1.0.0
     *
     * @param mixed ...$args arguments to pass to the constructor the first time the class is initialized only, if applicable
     * @return static
     */
    final public static function instance(...$args)
    {
        $class = static::class;
        if (!isset(static::$instance[$class])) {
            /** @phpstan-ignore-next-line */
            static::$instance[$class] = new static(...$args);
        }
        return static::$instance[$class];
    }
}
