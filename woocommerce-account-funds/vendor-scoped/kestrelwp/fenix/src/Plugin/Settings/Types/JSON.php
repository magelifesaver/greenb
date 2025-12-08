<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
/**
 * A setting type for JSON values.
 *
 * @since 1.1.0
 */
class JSON extends Text
{
    /**
     * Validates if the value can be stored as JSON.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return bool
     * @throws Setting_Validation_Exception
     */
    public function validate($value): bool
    {
        $is_valid = null === $value || is_scalar($value) || is_array($value);
        if (!$is_valid) {
            /* translators: Placeholder: %s - Invalid value type, e.g. "string" or "object" */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('Could not validate %s into a JSON value', static::plugin()->textdomain()), gettype($value))));
        }
        return $this->validate_subtype($value);
    }
    /**
     * Formats the JSON value as an array.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return array<int|string, mixed>
     */
    public function format($value): array
    {
        if (is_scalar($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            return [];
        }
        $value = json_decode($value, \true) ?: [];
        return $this->format_subtype($value);
    }
    /**
     * Sanitizes the value as JSON.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return string JSON
     */
    public function sanitize($value)
    {
        if (is_array($value)) {
            $sanitized = Arrays::array($value)->to_json();
        } elseif (Strings::is_json($value)) {
            $sanitized = $value;
        } else {
            $sanitized = '{}';
        }
        return $this->sanitize_subtype($sanitized);
    }
}
