<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
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
