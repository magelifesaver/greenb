<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
/**
 * A setting type for URLs.
 *
 * @since 1.1.0
 */
class Url extends Text
{
    /**
     * Validates if the value is a valid URL.
     *
     * @see Text::validate()
     *
     * @since 1.1.0
     *
     * @param string $value
     * @return bool
     * @throws Setting_Validation_Exception
     */
    protected function validate_subtype($value): bool
    {
        if (!filter_var($value, \FILTER_VALIDATE_URL)) {
            /* translators: Placeholder: %s - Invalid URL */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('"%s" is not a valid URL', static::plugin()->textdomain()), $value)));
        }
        return \true;
    }
    /**
     * Prepares the value for storage.
     *
     * @see Text::format()
     *
     * @since 1.1.0
     *
     * @param scalar $value
     * @return string
     */
    protected function sanitize_subtype($value)
    {
        return sanitize_url($value);
    }
}
