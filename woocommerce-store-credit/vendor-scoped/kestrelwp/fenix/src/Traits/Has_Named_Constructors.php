<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
use Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Strings;
use ReflectionClass;
/**
 * A trait for classes that implement named constructors mapped to their constants.
 *
 * @since 1.0.0
 */
trait Has_Named_Constructors
{
    /** @var string */
    protected string $name = '';
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param string $name
     */
    protected function __construct(string $name)
    {
        $this->name = strtoupper($name);
    }
    /**
     * Returns the name of the current instance.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function name(): string
    {
        $constants = (new ReflectionClass(static::class))->getConstants();
        return $constants[Strings::string($this->name)->snake_case()->uppercase()->to_string()] ?: '';
    }
    /**
     * Returns the list of names defined by the concrete class.
     *
     * @since 1.0.0
     *
     * @return string[]
     */
    protected static function constants(): array
    {
        $cases = (new ReflectionClass(static::class))->getConstants();
        return array_keys($cases);
    }
    /**
     * Maps constants to static method calls.
     *
     * @since 1.0.0
     *
     * @param string $method_name method name
     * @param array<mixed> $arguments
     * @return static
     * @throws Error
     */
    public static function __callStatic(string $method_name, array $arguments)
    {
        if (in_array(strtoupper($method_name), static::constants(), \true)) {
            // @phpstan-ignore-next-line
            return new static($method_name);
        }
        /** @var class-string $parent */
        $parent = get_parent_class(static::class);
        // invoke parent static method
        if ($parent && is_callable([$parent, '__callStatic'])) {
            // @phpstan-ignore-next-line
            return $parent::__callStatic($method_name, $arguments);
        }
        // invoked method is inaccessible
        if (method_exists(static::class, $method_name)) {
            throw new Error('Call to private method ' . static::class . '::' . $method_name);
            // phpcs:ignore
        }
        // invoked method is undefined
        throw new Error('Call to undefined method ' . static::class . '::' . $method_name);
        // phpcs:ignore
    }
}
