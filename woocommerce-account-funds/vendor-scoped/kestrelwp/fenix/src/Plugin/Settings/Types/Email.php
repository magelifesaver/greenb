<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
/**
 * A setting type for email addresses.
 *
 * @since 1.1.0
 */
class Email extends Text
{
    /** @var string default field type */
    protected string $field = Field::EMAIL;
    /**
     * Validates if the value is an actual email address.
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
        if (!is_email($value)) {
            /* translators: Placeholder: %s - Invalid email address */
            throw new Setting_Validation_Exception(esc_html(sprintf(__('"%s" is not a valid email address', static::plugin()->textdomain()), $value)));
        }
        return \true;
    }
    /**
     * Coerces the string value for storage.
     *
     * @see Text::format()
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return string
     */
    protected function sanitize_subtype($value)
    {
        return sanitize_email($value);
    }
}
