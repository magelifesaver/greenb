<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits;

defined('ABSPATH') or exit;
/**
 * A trait for classes that can be converted to a string.
 *
 * This can be used in classes that implement a `to_string()` method that want to implement the `__toString()` magic method using a PHP 8.0+ contract.
 *
 * @since 1.6.0
 *
 * @method string to_string()
 */
trait Is_Stringable
{
    /**
     * Returns a string representation of the object.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public function __toString(): string
    {
        // @phpstan-ignore-next-line
        if (is_callable([$this, 'to_string'])) {
            return $this->to_string();
        }
        // @phpstan-ignore-next-line
        return '';
    }
}
