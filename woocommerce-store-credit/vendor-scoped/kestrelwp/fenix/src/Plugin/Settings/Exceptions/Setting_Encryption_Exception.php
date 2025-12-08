<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
defined('ABSPATH') or exit;
/**
 * Exception thrown when an encryption error occurs.
 *
 * @since 1.1.0
 */
class Setting_Encryption_Exception extends Setting_Exception
{
    /** @var int */
    protected $code = Server_Error::INTERNAL_SERVER_ERROR;
}
