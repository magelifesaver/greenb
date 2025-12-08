<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
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
