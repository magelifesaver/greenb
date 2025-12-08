<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
/**
 * Text setting type.
 *
 * @since 1.1.0
 */
class Text extends Type
{
    /** @var string default field type */
    protected string $field = Field::TEXT;
    /**
     * Validates the value.
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
        if (is_numeric($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            /* translators: Placeholder: %s - Invalid value type, e.g. "boolean" or "object" */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Could not validate %s into a string', static::plugin()->textdomain()), gettype($value))));
        }
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        if ($this->get_min() && $strlen($value) < $this->get_min()) {
            /* translators: Placeholder: %d - Minimum text length */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Length is below the minimum of %d', static::plugin()->textdomain()), $this->get_min())));
        }
        if ($this->get_max() && $strlen($value) > $this->get_max()) {
            /* translators: Placeholder: %d - Maximum text length */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Length is above the maximum of %d', static::plugin()->textdomain()), $this->get_max())));
        }
        // phpcs:ignore
        if ($value && $this->has_choices() && !in_array($value, array_keys($this->get_choices()), !is_numeric($value))) {
            /* translators: Placeholder: %s - String of text submitted by the user */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('"%s" is not among the list of accepted values', static::plugin()->textdomain()), $value)));
        }
        if ($this->get_pattern() && !preg_match($this->get_pattern(), $value)) {
            /* translators: Placeholder: %s - String of text submitted by the user */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('"%s" does not match the required format', static::plugin()->textdomain()), $value)));
        }
        return $this->validate_subtype($value);
    }
    /**
     * Formats the raw value.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return string|string[]
     */
    public function format($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_map([$this, 'format'], $value);
        }
        if (is_numeric($value)) {
            $value = (string) $value;
        }
        $value = is_string($value) ? $value : '';
        return $this->format_subtype($value);
    }
    /**
     * Prepares the value for storage.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return string|string[]
     */
    public function sanitize($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            return array_map([$this, 'sanitize'], $value);
        }
        if (!is_string($value)) {
            $value = is_numeric($value) ? (string) $value : '';
        }
        if ($this->is_field(Field::TEXTAREA)) {
            $value = function_exists('wc_sanitize_textarea') ? wc_sanitize_textarea($value) : sanitize_textarea_field($value);
        } else {
            $value = sanitize_text_field($value);
        }
        return $this->sanitize_subtype($value);
    }
}
