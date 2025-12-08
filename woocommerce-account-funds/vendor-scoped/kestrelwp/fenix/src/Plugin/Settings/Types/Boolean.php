<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Booleans;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
/**
 * A setting type for booleans.
 *
 * @since 1.1.0
 */
class Boolean extends Type
{
    /** @var string default field type */
    protected string $field = Field::CHECKBOX;
    /**
     * Ensures the value is a boolean.
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
            foreach (array_values($value) as $item) {
                if (!$this->validate($item)) {
                    return \false;
                    // throws exception at this point
                }
            }
            return \true;
        }
        if (!in_array($value, [null, 'null', \true, \false, 1, 0, '1', '0', 'true', 'false', 'yes', 'no'], \true)) {
            /* translators: Placeholder: %s - Invalid value type, e.g. "string" or "object" */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Could not validate %s into a boolean value', static::plugin()->textdomain()), gettype($value))));
        }
        return $this->validate_subtype($value);
    }
    /**
     * Formats the raw value.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return array<string, bool>|bool
     */
    public function format($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            $formatted = [];
            foreach ($value as $key => $item) {
                $formatted[$key] = $this->format($item);
            }
            return $formatted;
        }
        $value = Booleans::from_value($value)->to_boolean();
        return $this->format_subtype($value);
    }
    /**
     * Converts boolean to string for storage.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return array<string, string>|string
     */
    public function sanitize($value)
    {
        if (is_array($value) && $this->is_multiple()) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitize($item);
            }
            return $sanitized;
        }
        $value = Booleans::from_value($value)->to_string();
        return $this->sanitize_subtype($value);
    }
}
