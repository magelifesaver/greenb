<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
/**
 * A setting type for integers.
 *
 * @since 1.1.0
 */
class Integer extends Number
{
    /** @var int default increment */
    protected $increments = 1;
    /**
     * Formats the value as an integer.
     *
     * @see Number::format()
     *
     * @since 1.1.0
     *
     * @param numeric $value
     * @return int|numeric
     *
     * @phpstan-return int
     */
    protected function format_subtype($value)
    {
        return (int) $value;
    }
    /**
     * Prepares the value for storage as an integer.
     *
     * @see Number::sanitize()
     *
     * @since 1.1.0
     *
     * @param numeric $value
     * @return float|int
     *
     * @phpstan-return int
     */
    protected function sanitize_subtype($value)
    {
        return (int) $value;
    }
}
