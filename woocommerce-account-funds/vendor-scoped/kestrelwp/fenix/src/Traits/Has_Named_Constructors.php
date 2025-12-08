<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
use Error;
use ReflectionClass;
/**
 * A trait for classes that implement named constructors mapped to their constants.
 *
 * @NOTE A $default property that holds the default value can be defined in the concrete class.
 *
 * @see Has_Named_Constructors::make() will return the instance holding the default value if no value is provided.
 *
 * @since 1.0.0
 */
trait Has_Named_Constructors
{
    use Is_Stringable;
    /** @var string */
    protected string $name = '';
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param string $name
     */
    protected function __construct(string $name = '')
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
        return $this->name;
    }
    /**
     * Returns the value of the current instance.
     *
     * @since 1.6.0
     *
     * @return mixed|string
     * */
    public function value()
    {
        $constants = (new ReflectionClass(static::class))->getConstants();
        return $constants[$this->name()] ?? '';
    }
    /**
     * Returns the default value, if specified.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public static function default_value(): string
    {
        // @phpstan-ignore-next-line type safety check
        if (property_exists(static::class, 'default') && is_string(static::$default)) {
            return trim(static::$default);
        }
        return '';
    }
    /**
     * Returns the list of names defined by the concrete class.
     *
     * @since 1.0.0
     *
     * @return array<string, string>
     */
    protected static function constants(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
    /**
     * Creates a new instance of the class with the given name.
     *
     * @since 1.6.0
     *
     * @param static|string|null $value value of one of the constants defined by the concrete class
     * @return static
     */
    public static function make($value = null)
    {
        if ($value instanceof static) {
            // @phpstan-ignore-next-line
            return $value;
        }
        $default_value = trim(static::default_value());
        $constants = self::constants();
        if ($value && in_array($value, $constants, \true)) {
            $values = array_flip($constants);
            $match = $values[$value];
        } elseif ($value && array_key_exists(strtoupper($value), $constants)) {
            $match = $value;
        } else {
            $values = array_flip($constants);
            $match = $values[$default_value] ?: $default_value;
        }
        // @phpstan-ignore-next-line
        return new static($match);
    }
    /**
     * Returns the string representation of the current instance.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public function to_string(): string
    {
        return $this->value();
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
        if (in_array(strtoupper($method_name), array_keys(static::constants()), \true)) {
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
