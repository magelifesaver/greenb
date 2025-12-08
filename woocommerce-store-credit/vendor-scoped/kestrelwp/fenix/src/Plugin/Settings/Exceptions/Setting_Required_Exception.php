<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
defined('ABSPATH') or exit;
/**
 * Exception thrown when trying to save a required setting with a null value.
 *
 * @since 1.1.0
 */
class Setting_Required_Exception extends Setting_Exception
{
    /** @var int */
    protected $code = Client_Error::FORBIDDEN;
}
