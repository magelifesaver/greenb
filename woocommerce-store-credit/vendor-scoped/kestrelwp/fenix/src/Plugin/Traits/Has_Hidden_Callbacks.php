<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits;

defined('ABSPATH') or exit;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Error;
/**
 * Provides a way to flag private or protected methods as externally accessible.
 *
 * This is useful to register WordPress hook without having to make the associated callback method public.
 *
 * Inspired by WooCommerce internal {@see AccessiblePrivateMethods}.
 *
 * @since 1.0.0
 */
trait Has_Hidden_Callbacks
{
    /** @var array<string, string> */
    protected array $accessible_callbacks = [];
    /** @var array<string, string> */
    protected static array $accessible_static_callbacks = [];
    /**
     * Registers a WordPress action.
     *
     * @see \add_action()
     *
     * @since 1.0.0
     *
     * @param string $hook_name
     * @param array<mixed>|callable|callable-string|string $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    protected static function add_action(string $hook_name, $callback, int $priority = 10, int $accepted_args = 1): void
    {
        static::process_callback_before_hooking($callback);
        add_action($hook_name, $callback, $priority, $accepted_args);
    }
    /**
     * Register a WordPress filter.
     *
     * @see \add_filter()
     *
     * @since 1.0.0
     *
     * @param string $hook_name
     * @param array<mixed>|callable|callable-string|string $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    protected static function add_filter(string $hook_name, $callback, int $priority = 10, int $accepted_args = 1): void
    {
        static::process_callback_before_hooking($callback);
        add_filter($hook_name, $callback, $priority, $accepted_args);
    }
    /**
     * Performs the required processing to a callback before invoking the WordPress 'add_action' or 'add_filter' function.
     *
     * @since 1.0.0
     *
     * @param array<mixed>|callable|callable-string|string $callback
     * @return void
     */
    protected static function process_callback_before_hooking($callback): void
    {
        if (!is_array($callback) || count($callback) < 2) {
            return;
        }
        $first_item = $callback[0];
        if (static::class === $first_item) {
            static::flag_static_method_as_accessible($callback[1]);
        } elseif (is_object($first_item) && get_class($first_item) === static::class) {
            $first_item->flag_method_as_accessible($callback[1]);
        }
    }
    /**
     * Flags a private or protected instance method of this class as externally accessible.
     *
     * @since 1.0.0
     *
     * @param string $method_name
     * @return bool success
     */
    protected function flag_method_as_accessible(string $method_name): bool
    {
        /** @NOTE that an "is_callable" check would be useless here: "is_callable" always returns true if the class implements __call(). */
        if (method_exists($this, $method_name)) {
            $this->accessible_callbacks[$method_name] = $method_name;
            return \true;
        }
        return \false;
    }
    /**
     * Flags a private or protected static method of this class as externally accessible.
     *
     * @since 1.0.0
     *
     * @param string $method_name
     * @return bool success
     */
    protected static function flag_static_method_as_accessible(string $method_name): bool
    {
        /** @NOTE that an "is_callable" check would be useless here: "is_callable" always returns true if the class implements __call(). */
        if (method_exists(static::class, $method_name)) {
            static::$accessible_static_callbacks[$method_name] = $method_name;
            return \true;
        }
        return \false;
    }
    /**
     * Undefined or inaccessible instance method call handler.
     *
     * @since 1.0.0
     *
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     * @throws Error
     */
    public function __call(string $name, array $arguments)
    {
        // invoke inaccessible hook callback method
        if (isset($this->accessible_callbacks[$name])) {
            return call_user_func_array([$this, $name], $arguments);
        }
        /** @var class-string $parent */
        $parent = get_parent_class(static::class);
        // @phpstan-ignore-next-line
        if ($parent && is_callable([$parent, '__call'])) {
            // invoke parent method
            return $parent::__call($name, $arguments);
        }
        // @phpstan-ignore-next-line
        if (method_exists($this, $name)) {
            // invoked method is inaccessible
            throw new Error('Call to private method ' . static::class . '::' . $name);
            // phpcs:ignore
        }
        // invoked method is undefined
        throw new Error('Call to undefined method ' . static::class . '::' . $name);
        // phpcs:ignore
    }
    /**
     * Undefined or inaccessible static method call handler.
     *
     * @since 1.0.0
     *
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     * @throws Error
     */
    public static function __callStatic(string $name, array $arguments)
    {
        // invoke inaccessible static hook callback method
        if (isset(static::$accessible_static_callbacks[$name])) {
            return call_user_func_array([static::class, $name], $arguments);
        }
        /** @var class-string $parent */
        $parent = get_parent_class(static::class);
        // invoke parent static method
        if ($parent && is_callable([$parent, '__callStatic'])) {
            return $parent::__callStatic($name, $arguments);
        }
        // invoked method is inaccessible
        if (method_exists(static::class, $name)) {
            throw new Error('Call to private method ' . static::class . '::' . $name);
            // phpcs:ignore
        }
        // invoked method is undefined
        throw new Error('Call to undefined method ' . static::class . '::' . $name);
        // phpcs:ignore
    }
}
