<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
use Throwable;
/**
 * Exception thrown when a request is invalid.
 *
 * @since 1.0.0
 */
class Invalid_Request_Exception extends Request_Exception
{
    /**
     * Constructor.
     *
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct(Client_Error::BAD_REQUEST, $message, $previous);
    }
}
