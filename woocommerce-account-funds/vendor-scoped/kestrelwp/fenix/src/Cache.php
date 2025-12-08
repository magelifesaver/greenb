<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
defined('ABSPATH') or exit;
/**
 * Cache handler.
 *
 * This works both by storing data in memory and optionally by persisting it in transients.
 *
 * Usage:
 *
 * ```php
 * $cache  = Cache::put( 'my_key', 'my_value' );
 * $cached = Cache::key( 'my_key' )->get(); // 'my_value'
 * ```
 *
 * @since 1.0.0
 */
class Cache
{
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var int persistent cache duration, in seconds (default 1 hour) */
    protected int $expiration = 3600;
    /** @var string the cache key - this will be used in the object cache key and to build the transient name */
    protected string $key = '';
    /**
     * The object cache storage.
     *
     * @var array<string, array<string, mixed>>
     *
     * @phpstan-var array<string, array{value: mixed, time: int, persist: bool}>
     */
    protected static array $storage = [];
    /**
     * Gets a cache instance with the given key.
     *
     * @since 1.0.0
     *
     * @param string $item
     *
     * @phpstan-param non-empty-string $item
     *
     * @return Cache
     */
    public static function key(string $item): Cache
    {
        return self::instance()->set_key($item);
    }
    /**
     * Puts a value in the cache for the given key.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param mixed $value
     * @param bool $persist
     * @param int|null $expiration
     * @return Cache
     */
    public static function put(string $key, $value, bool $persist = \true, ?int $expiration = null): Cache
    {
        return self::key($key)->set($value, $persist, $expiration);
    }
    /**
     * Flushes all cache.
     *
     * @since 1.0.0
     *
     * @param bool $persisted whether to also clear the cache persisted in transients
     * @return void
     */
    public static function flush(bool $persisted = \false): void
    {
        if ($persisted) {
            foreach (self::$storage as $key => $value) {
                if (!empty($value['persist'])) {
                    self::key($key)->forget($persisted);
                }
            }
        }
        self::$storage = [];
    }
    /**
     * Sets the current cache key.
     *
     * @since 1.0.0
     *
     * @param string $key
     *
     * @phpstan-param non-empty-string $key
     *
     * @return $this
     */
    protected function set_key(string $key): Cache
    {
        // @phpstan-ignore-next-line
        if ('' === $key) {
            $key = uniqid();
        }
        if (!array_key_exists($key, self::$storage)) {
            self::$storage[$key] = ['value' => null, 'time' => null, 'persist' => \false];
        }
        $this->key = $key;
        return $this;
    }
    /**
     * Gets the current cache key.
     *
     * @since 1.0.0
     *
     * @return string
     *
     * @phpstan-return non-empty-string
     */
    protected function get_key(): string
    {
        return $this->key;
    }
    /**
     * Sets the cache expiration in seconds.
     *
     * @since 1.0.0
     *
     * @param positive-int $expiration
     *
     * @phpstan-param int<60, max> $expiration
     *
     * @return $this
     */
    public function set_expiration(int $expiration): Cache
    {
        $this->expiration = max(60, $expiration);
        return $this;
    }
    /**
     * Gets the cache expiration in seconds.
     *
     * @return positive-int
     *
     * @phpstan-return int<60, max>
     */
    public function get_expiration(): int
    {
        return $this->expiration;
    }
    /**
     * Gets the persistent cache key.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function get_persistence_key(): string
    {
        return self::plugin()->key($this->get_key());
    }
    /**
     * Clears the current cache.
     *
     * @since 1.0.0
     *
     * @param bool $persisted whether to also clear the cache persisted in a transient
     * @return $this
     */
    public function forget(bool $persisted = \true): Cache
    {
        if ($persisted) {
            $this->forget_persisted();
        }
        unset(self::$storage[$this->get_key()]);
        return $this;
    }
    /**
     * Clears the persisted cache that was set to  a transient.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function forget_persisted(): void
    {
        delete_transient($this->get_persistence_key());
    }
    /**
     * Gets an item from the cache, or execute the given callable and store the result.
     *
     * @since 1.0.0
     *
     * @param callable $cacheable function that returns value to cache
     * @param bool $persist whether to persist the cache to a transient
     * @param positive-int|null $expiration optional, cache expiration in seconds
     *
     * @phpstan-param int<60, max>|null $expiration
     *
     * @return mixed
     */
    public function remember(callable $cacheable, bool $persist = \true, ?int $expiration = null)
    {
        $value = $this->get();
        if (null === $value) {
            $value = $cacheable();
            $this->set($value, $persist, $expiration);
        }
        return $value;
    }
    /**
     * Gets a cached value.
     *
     * @since 1.0.0
     *
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get($default = null)
    {
        $cached = self::$storage[$this->get_key()] ?? [];
        if (array_key_exists('value', $cached) && !empty($cached['time'])) {
            return $cached['value'];
        }
        $persisted = $this->persisted();
        if ($persisted !== \false) {
            return $persisted;
        }
        return $default;
    }
    /**
     * Gets a cached value from a transient.
     *
     * @since 1.0.0
     *
     * @return false|mixed
     */
    protected function persisted()
    {
        $value = get_transient($this->get_persistence_key());
        if ($value !== \false) {
            $this->set($value, \false);
        }
        return $value;
    }
    /**
     * Checks if a valid change has occurred that cache should be set.
     *
     * @since 1.0.0
     *
     * @param mixed $value
     * @return bool
     */
    protected function has_changed($value): bool
    {
        $cached = self::$storage[$this->get_key()] ?? [];
        if (!array_key_exists('value', $cached) || empty($cached['time'])) {
            return \true;
        }
        $cached_value = $cached['value'];
        return is_object($value) ? $cached_value != $value : $cached_value !== $value;
        // phpcs:ignore
    }
    /**
     * Puts a value in the cache for the current storage key.
     *
     * @since 1.0.0
     *
     * @param mixed $value value to cache
     * @param bool $persist whether to persist the cache in a transient
     * @param int|null $expiration optional, cache expiration in seconds
     * @return $this
     */
    public function set($value, bool $persist = \true, ?int $expiration = null): Cache
    {
        if (!$this->has_changed($value)) {
            return $this;
        }
        self::$storage[$this->get_key()] = ['value' => $value, 'time' => time(), 'persist' => $persist];
        if ($persist) {
            if (null !== $expiration) {
                $this->set_expiration($expiration);
            }
            $this->persist($value);
        }
        return $this;
    }
    /**
     * Updates a value without changing the current expiration.
     *
     * Unless not persisted, this will update the transient value without setting a new transient, so it will not affect its expiry.
     *
     * @since 1.7.2
     *
     * @param mixed $value value to cache
     * @param bool $persist whether to persist the cache in a transient
     * @return $this
     */
    public function update($value, bool $persist = \true): Cache
    {
        $transient_key = '_transient_' . $this->get_persistence_key();
        if ($persist) {
            $persisted = get_option($transient_key);
        } else {
            $persisted = \false;
        }
        if (\false === $persisted) {
            $this->set($value, $persist);
        } else {
            $this->set($value, \false);
            update_option($transient_key, $value);
        }
        return $this;
    }
    /**
     * Sets a value to be cached in a transient.
     *
     * @since 1.0.0
     *
     * @param mixed $value
     * @return void
     */
    protected function persist($value): void
    {
        set_transient($this->get_persistence_key(), $value, $this->get_expiration());
    }
}
