<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers;

defined('ABSPATH') or exit;
/**
 * Helper class for type parsing and manipulation.
 *
 * @since 1.4.0
 */
final class Type
{
    /**
     * Parses an array value to an object for manipulation and type safety.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @param array<mixed> $default
     * @return Arrays
     */
    public static function array($value, array $default = []): Arrays
    {
        if (Strings::is_json($value)) {
            $value = json_decode($value, \true);
        } elseif (is_object($value) && is_callable([$value, 'to_array'])) {
            $value = $value->to_array();
        } elseif (is_object($value) && is_callable([$value, 'toArray'])) {
            $value = $value->toArray();
        }
        if (!is_array($value)) {
            $value = $default;
        }
        return Arrays::array($value);
    }
    /**
     * Returns an array of instances of the given type.
     *
     * @since 1.4.0
     *
     * @template T
     *
     * @param mixed $value
     * @param class-string<T> $type a classname used to filter the values in the array
     * @param bool $maintain_index
     * @return T[]
     */
    public static function array_of($value, string $type, bool $maintain_index = \true): array
    {
        $value = self::array($value)->to_array();
        $array = array_filter($value, static function ($item) use ($type) {
            return $item instanceof $type;
        });
        return $maintain_index ? $array : array_values($array);
    }
    /**
     * Returns an array of class-string values of the given type.
     *
     * @since 1.4.0
     *
     * @template T of object
     *
     * @param mixed $value
     * @param class-string<T> $type a classname used to filter the values in the array
     * @param bool $maintain_index
     * @return class-string<T>[]
     */
    public static function array_of_class_strings($value, string $type, bool $maintain_index = \true): array
    {
        $value = self::array($value)->to_array();
        $array = array_filter($value, function ($item) use ($type) {
            return is_string($item) && is_a($item, $type, \true);
        });
        return $maintain_index ? $array : array_values($array);
    }
    /**
     * Returns an array of strings.
     *
     * @param mixed $value
     * @param bool $maintain_index
     * @return string[]
     */
    public static function array_of_strings($value, bool $maintain_index = \true): array
    {
        $value = self::array($value)->to_array();
        $array = array_filter($value, static function ($item) {
            return is_string($item);
        });
        return $maintain_index ? $array : array_values($array);
    }
    /**
     * Returns an array of integers.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @param bool $maintain_index
     * @return int[]
     */
    public static function array_of_integers($value, bool $maintain_index = \true): array
    {
        $array = self::array($value)->to_array();
        foreach ($array as $index => $item) {
            // skip if already an integer
            if (is_int($item)) {
                continue;
            }
            if (is_numeric($item) && (string) (int) $item === (string) $item) {
                // numeric string that represents an integer (including '0', '-5', etc.)
                $array[$index] = (int) $item;
            } else {
                unset($array[$index]);
            }
        }
        return $maintain_index ? $array : array_values($array);
    }
    /**
     * Parses a boolean value to an object for manipulation and type safety.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @return Booleans
     */
    public static function bool($value): Booleans
    {
        return Booleans::from_value($value);
    }
    /**
     * Returns a float value or a default value if the input is not a float.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @param float $default
     * @return float
     */
    public static function float($value, float $default = 0.0): float
    {
        return is_numeric($value) ? (float) $value : $default;
    }
    /**
     * Returns a float value or null if the input is not a float.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @return float|null
     *
     * @phpstan-return ($value is numeric ? float : null)
     */
    public static function float_or_null($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
    /**
     * Returns an integer value or a default value if the input is not an integer.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @param int $default
     * @return int
     */
    public static function int($value, int $default = 0): int
    {
        if (is_int($value)) {
            return (int) $value;
        }
        return filter_var($value, \FILTER_VALIDATE_INT) !== \false ? (int) $value : $default;
    }
    /**
     * Returns an integer value or null if the input is not an integer.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @return int|null
     *
     * @phpstan-return ($value is int ? int : null)
     */
    public static function int_or_null($value): ?int
    {
        if (is_int($value)) {
            return (int) $value;
        }
        return filter_var($value, \FILTER_VALIDATE_INT) !== \false ? (int) $value : null;
    }
    /**
     * Returns a scalar value or a default value if the input is not scalar.
     *
     * @since 1.4.0
     *
     * @template TValue of scalar|null
     * @template TDefault of scalar|null
     *
     * @param TValue $value
     * @param TDefault|null $default
     * @return scalar|null
     *
     * @phpstan-return (TValue is scalar ? TValue : TDefault is scalar ? TDefault : null)
     */
    public static function scalar($value, $default = null)
    {
        if (is_scalar($value)) {
            return $value;
        }
        return is_scalar($default) ? $default : null;
    }
    /**
     * Parses a string value to an object for manipulation and type safety.
     *
     * @since 1.4.0
     *
     * @param mixed $value
     * @param string $default
     * @return Strings
     */
    public static function string($value, string $default = ''): Strings
    {
        if (is_numeric($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            $value = $default;
        }
        return Strings::string($value);
    }
    /**
     * Returns a string or null if the input is not a string.
     *
     * @param mixed $value
     * @return string|null
     *
     * @phpstan-return ($value is string ? string : null)
     */
    public static function string_or_null($value): ?string
    {
        return is_string($value) ? $value : null;
    }
    /**
     * Returns a non-empty string or null if the input is empty.
     *
     * @since 1.4.0
     *
     * @param string $value
     * @return string|null
     *
     * @phpstan-return ($value is non-empty-string ? string : null)
     */
    public static function non_empty_string_or_null(string $value): ?string
    {
        $value = Strings::string($value);
        return $value->is_empty() ? null : $value->to_string();
    }
}
