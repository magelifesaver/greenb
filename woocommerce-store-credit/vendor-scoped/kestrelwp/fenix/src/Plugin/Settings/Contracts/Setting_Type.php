<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
defined('ABSPATH') or exit;
/**
 * Contract for setting types.
 *
 * @since 1.1.0
 */
interface Setting_Type
{
    /**
     * Determines if the setting type is for multiple values.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_multiple(): bool;
    /**
     * Validates a value according to the intended setting type.
     *
     * The value should be validated before it is sanitized and stored.
     *
     * @since 1.1.0
     *
     * @param mixed $value value to be validated
     * @return bool
     * @throws Setting_Validation_Exception if the value is invalid
     */
    public function validate($value): bool;
    /**
     * Returns the formatted value.
     *
     * This should return a value for display or internal logic purposes.
     * In most cases this will just return a simple scalar value.
     * For complex types, this could also return an object.
     *
     * @since 1.1.0
     *
     * @param mixed $value value to be formatted
     * @return mixed
     */
    public function format($value);
    /**
     * Sanitizes and prepares a value for database storage.
     *
     * @since 1.1.0
     *
     * @param mixed $value value to be sanitized
     * @return array<mixed>|scalar
     */
    public function sanitize($value);
}
