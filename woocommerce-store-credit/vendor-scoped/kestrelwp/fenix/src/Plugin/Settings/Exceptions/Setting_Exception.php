<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Exceptions\Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error\Server_Error;
use Throwable;
/**
 * Exception for settings.
 *
 * @since 1.1.0
 */
class Setting_Exception extends Exception
{
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $message, Throwable $previous = null)
    {
        if ($previous && is_numeric($previous->getCode())) {
            $this->code = (int) $previous->getCode();
        } else {
            $this->code = Server_Error::INTERNAL_SERVER_ERROR;
        }
        parent::__construct($message, $previous);
    }
}
