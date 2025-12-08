<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
/**
 * A setting type for floating numbers.
 */
class Decimal extends Number
{
    /** @var float default increment */
    protected $increments = 0.01;
    /**
     * Formats the value as a float.
     *
     * @see Number::format()
     *
     * @since 1.1.0
     *
     * @param numeric $value
     * @return float
     */
    protected function format_subtype($value)
    {
        return (float) $value;
    }
    /**
     * Prepares the value for storage as a float.
     *
     * @see Number::sanitize()
     *
     * @since 1.1.0
     *
     * @param numeric $value
     * @return float
     */
    protected function sanitize_subtype($value)
    {
        return (float) $value;
    }
}
