<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
/**
 * Amount setting type, like a financial amount.
 *
 * This will handle values in cents, and format them as a decimal number.
 * It will try to use WooCommerce settings for decimals and separators if available, otherwise they will need to be specified manually.
 * Does not take thousands separators into account. If thousands separators are needed, the output will need to be formatted accordingly
 *
 * @since 1.1.0
 *
 * @method int get_decimals()
 * @method string get_decimal_separator()
 * @method bool get_signed()
 * @method $this set_decimals( int $decimals )
 * @method $this set_decimal_separator( string $decimal_separator )
 * @method $this set_signed( bool $signed )
 */
class Amount extends Text
{
    /** @var string default field type */
    protected string $field = Field::TEXT;
    /** @var int precision */
    protected int $decimals = 2;
    /** @var string */
    protected string $decimal_separator = '.';
    /** @var bool whether negative amounts are supported, default false */
    protected bool $signed = \false;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        // set defaults to WooCommerce settings, if available
        $args = wp_parse_args($args, ['decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2, 'decimal_separator' => function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.', 'signed' => \false]);
        parent::__construct($args);
    }
    /**
     * Determines if the amount is signed (can be negative).
     *
     * @since 1.7.0
     *
     * @return bool
     */
    public function is_signed(): bool
    {
        return \true === $this->get_signed();
    }
    /**
     * Validates the financial amount value (e.g. 12.34 or 12,34 as a numeric string with a variable decimal separator).
     *
     * @since 1.7.0
     *
     * @param mixed $value
     * @return bool
     * @throws Setting_Validation_Exception
     */
    public function validate($value): bool
    {
        $value = $this->format($value);
        if (is_array($value) && $this->is_multiple()) {
            return array_reduce($value, function ($carry, $item) {
                return $carry && $this->validate($item);
            }, \true);
        }
        if ('' === $value && !$this->get_min() && !$this->get_max()) {
            return \true;
        }
        if (is_numeric($value)) {
            $value = (string) $value;
        }
        $value = str_replace($this->get_decimal_separator(), '.', $value);
        if (!is_numeric($value)) {
            /* translators: Placeholder: %s - Invalid value type, e.g. "boolean" or "object" */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Could not validate %s into a valid amount', static::plugin()->textdomain()), gettype($value))));
        }
        $value = (float) $value;
        if ($this->get_min() && $value < $this->get_min()) {
            /* translators: Placeholder: %d - Minimum text length */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('The amount is below the minimum of %d', static::plugin()->textdomain()), $this->get_min())));
        }
        if ($this->get_max() && $value > $this->get_max()) {
            /* translators: Placeholder: %d - Maximum text length */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('The amount is above the maximum of %d', static::plugin()->textdomain()), $this->get_max())));
        }
        return $this->validate_subtype($value);
    }
    /**
     * Formats the value in cents as a formatted number (eg. 1234 becomes 12.34 or 12,34).
     *
     * @see Text::format() override instead of formatting subtype
     *
     * @since 1.7.0
     *
     * @param mixed $value
     * @return numeric-string|numeric-string[]|string|string[]
     */
    public function format($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_map([$this, 'format'], $value);
        }
        if (null === $value || '' === $value) {
            return '';
            // allow empty values
        }
        if (is_int($value)) {
            $value = $value / pow(10, $this->get_decimals());
        } elseif (is_string($value) || is_float($value)) {
            $value = str_replace($this->get_decimal_separator(), '.', (string) $value);
        } else {
            $value = 0;
        }
        return $this->format_subtype(number_format((float) $value, $this->get_decimals(), $this->get_decimal_separator(), ''));
    }
    /**
     * Sanitize the value as an integer (financial amount in cents, e.g. 12,34 or 12.34 becomes 1234).
     *
     * @see Text::sanitize() override instead of sanitizing subtype
     *
     * @since 1.7.0
     *
     * @param mixed $value the value to sanitize
     * @return int|int[]|null
     *
     * @phpstan-ignore-next-line
     */
    public function sanitize($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_map([$this, 'sanitize'], $value);
        }
        if (is_null($value) || '' === $value) {
            return null;
            // allow empty values
        }
        $normalized_string = sanitize_text_field($value);
        $normalized_string = str_replace($this->get_decimal_separator(), '.', $normalized_string);
        $precision = WooCommerce::is_active() ? WooCommerce::rounding_precision() : 0;
        $value = (int) round(floatval($normalized_string) * pow(10, $this->get_decimals()), $precision, \PHP_ROUND_HALF_UP);
        return $this->sanitize_subtype($this->is_signed() ? $value : abs($value));
    }
}
