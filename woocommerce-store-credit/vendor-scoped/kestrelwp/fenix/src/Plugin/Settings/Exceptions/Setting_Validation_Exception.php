<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
defined('ABSPATH') or exit;
/**
 * Exception for settings validation errors.
 *
 * @since 1.1.0
 */
class Setting_Validation_Exception extends Setting_Exception
{
    /** @var int */
    protected $code = Client_Error::BAD_REQUEST;
}
