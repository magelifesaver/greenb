<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
defined('ABSPATH') or exit;
/**
 * Amount setting type, like a financial amount.
 *
 * @since 1.1.0
 *
 * @method int get_decimals()
 * @method string get_decimal_separator()
 * @method string get_thousands_separator()
 * @method $this set_decimals( int $decimals )
 * @method $this set_decimal_separator( string $decimal_separator )
 * @method $this set_thousands_separator( string $thousands_separator )
 */
class Amount extends Integer
{
    /** @var string default field type */
    protected string $field = Field::TEXT;
    /** @var int precision */
    protected int $decimals = 2;
    /** @var string */
    protected string $decimal_separator = '.';
    /** @var string */
    protected string $thousands_separator = ',';
    /**
     * Constructor.
     *
     * @see Integer::__construct()
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        // set defaults to WooCommerce settings, if available
        $args = wp_parse_args($args, ['decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2, 'decimal_separator' => function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.', 'thousands_separator' => function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ',']);
        parent::__construct($args);
    }
    /**
     * Formats the value as a formatted number.
     *
     * @see Integer::format()
     *
     * @param mixed $value
     * @return numeric-string
     *
     * @phpstan-return numeric
     */
    protected function format_subtype($value)
    {
        return number_format((int) $value, $this->get_decimals(), $this->get_decimal_separator(), $this->get_thousands_separator());
    }
    /**
     * Sanitize the value as an integer.
     *
     * @see Integer::sanitize()
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return int
     */
    protected function sanitize_subtype($value)
    {
        $value = str_replace($this->get_thousands_separator(), '', $value);
        $value = str_replace($this->get_decimal_separator(), '.', $value);
        return (int) $value * pow(10, $this->get_decimals());
    }
}
