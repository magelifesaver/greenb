<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers;

defined('ABSPATH') or exit;
/**
 * Helper class for boolean operations.
 *
 * @since 1.3.0
 */
final class Booleans
{
    /** @var bool */
    private static bool $value;
    /**
     * Constructor.
     *
     * @since 1.3.0
     *
     * @param bool $value
     */
    private function __construct(bool $value)
    {
        self::$value = $value;
    }
    /**
     * Initializes a boolean value as an object to perform operations on.
     *
     * @param bool|string $value
     * @return Booleans
     */
    public static function bool($value): Booleans
    {
        return self::from_value($value);
    }
    /**
     * Return truthy values.
     *
     * @since 1.3.0
     *
     * @return array<bool|int|string>
     */
    public static function truthy_values(): array
    {
        return [1, \true, 'true', '1', 'yes', 'on'];
    }
    /**
     * Returns a list of falsey values.
     *
     * @since 1.3.0
     *
     * @param bool $include_null
     * @return array<bool|int|string|null>
     */
    public static function falsey_values(bool $include_null = \false): array
    {
        $values = [0, \false, 'false', '0', 'no', 'off'];
        if ($include_null) {
            $values[] = null;
        }
        return $values;
    }
    /**
     * Returns a boolean value based on a truthy or falsey value.
     *
     * @since 1.3.0
     *
     * @param mixed $value
     * @return Booleans
     */
    public static function from_value($value): Booleans
    {
        return new self(in_array($value, self::truthy_values(), \true));
    }
    /**
     * Returns a boolean value as a string.
     *
     * @since 1.3.0
     *
     * @param string $true e.g. "yes", "on", "true"... (default "yes")
     * @param string $false e.g. "no", "off", "false"... (default "no")
     * @return string
     */
    public function to_string(string $true = 'yes', string $false = 'no'): string
    {
        return self::$value ? $true : $false;
    }
    /**
     * Returns the current instance value.
     *
     * @since 1.3.0
     *
     * @return bool
     */
    public function to_boolean(): bool
    {
        return self::$value;
    }
}
