<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
use Exception;
use ReflectionClass;
use ValueError;
// phpcs:disable
/**
 * Enables enum-like syntax pre PHP 8.1.
 *
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 *
 * @NOTE The coding style of this trait does not follow the rest of the coding standards because it's meant to be a drop-in replacement for PHP 8.1 enums.
 *
 * @since 1.0.0
 */
trait Is_Enum
{
    /**
     * Maps a scalar to an enum value or throws an error.
     *
     * @since 1.1.0
     *
     * @param int|string $value the scalar value to map to an enum case
     * @return static::*
     * @throws Exception|ValueError if the value is not a valid backing value for the enum
     */
    public static function from($value)
    {
        if (!in_array($value, static::values(), \true)) {
            $error_message = sprintf('"%s" is not a valid backing value for enum "%s"', $value, static::class);
            // @phpstan-ignore-next-line PHP 7.4 backwards compatibility
            if (class_exists(ValueError::class)) {
                throw new ValueError($error_message);
            } else {
                throw new Exception($error_message);
            }
        }
        return $value;
    }
    /**
     * Maps a scalar to an enum value or null.
     *
     * @since 1.0.0
     *
     * @param int|string $value the scalar value to map to an enum case
     * @return static::*|null
     */
    public static function tryFrom($value)
    {
        return in_array($value, static::values(), \true) ? $value : null;
    }
    /**
     * Fetches the values for the enum.
     *
     * @since 1.0.0
     *
     * @return array<static::*> an array of enum values
     */
    public static function values(): array
    {
        return array_values(static::cases());
    }
    /**
     * Returns an associative array where the enum names are the keys and the enum values are the values.
     *
     * @since 1.0.0
     *
     * @return array<string, static::*>
     */
    public static function cases(): array
    {
        /** @var array<string, static::*> $cases */
        $cases = (new ReflectionClass(static::class))->getConstants();
        return $cases;
    }
}
// phpcs:enable
