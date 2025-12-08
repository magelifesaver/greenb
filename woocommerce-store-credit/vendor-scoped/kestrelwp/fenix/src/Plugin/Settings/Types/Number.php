<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
defined('ABSPATH') or exit;
/**
 * Numeric setting type.
 *
 * @since 1.1.0
 *
 * @method float|int|null get_increments()
 * @method $this set_increments( float|int|null $increments )
 */
class Number extends Type
{
    /** @var string default field type */
    protected string $field = Field::NUMBER;
    /** @var float|int|null increment value */
    protected $increments = null;
    /**
     * Ensures the value is numeric.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return bool
     * @throws Setting_Validation_Exception
     */
    public function validate($value): bool
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_reduce($value, function ($carry, $item) {
                return $carry && $this->validate($item);
            }, \true);
        }
        if (null === $value) {
            return \true;
        }
        if (!is_numeric($value)) {
            /* translators: Placeholder: %s - Invalid value type, e.g. "boolean" or "object" */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Could not validate %s into a number', static::plugin()->textdomain()), gettype($value))));
        }
        if ($this->get_min() && (float) $value < $this->get_min()) {
            /* translators: Placeholders: %s - Numerical value submitted by the user, %d - Minimum amount */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('%1$s is below the minimum allowed of %2$d', static::plugin()->textdomain()), $value, $this->get_min())));
        }
        if ($this->get_max() && (float) $value > $this->get_max()) {
            /* translators: Placeholders: %s - Numerical value submitted by the user, %d - Maximum amount */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('%1$s is above the maximum allowed of %2$d', static::plugin()->textdomain()), $value, $this->get_max())));
        }
        if ($value && $this->has_choices() && !in_array($value, array_keys($this->get_choices()), \true)) {
            /* translators: Placeholder: %s - Numeric value submitted by the user */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('"%s" is not among the list of accepted values', static::plugin()->textdomain()), $value)));
        }
        if ($this->get_pattern() && !preg_match($this->get_pattern(), $value)) {
            /* translators: Placeholder: %s - Numeric value submitted by the user */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('"%s" does not match the required format', static::plugin()->textdomain()), $value)));
        }
        return $this->validate_subtype($value);
    }
    /**
     * Formats the value as a numeric string.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return numeric|numeric[]
     */
    public function format($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_map([$this, 'format'], $value);
        }
        $value = is_numeric($value) ? $value : 0;
        return $this->format_subtype($value);
    }
    /**
     * Parses the value as a numeric string.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return numeric|numeric[]
     */
    public function sanitize($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_map([$this, 'sanitize'], $value);
        }
        $value = is_numeric($value) ? $value : 0;
        return $this->sanitize_subtype($value);
    }
}
