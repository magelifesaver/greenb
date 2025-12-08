<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Exceptions\Exception;
use Throwable;
/**
 * Response exception.
 *
 * @since 1.0.0
 */
class Response_Exception extends Exception
{
    /**
     * Constructor.
     *
     * @param int $error_code
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(int $error_code, string $message, ?Throwable $previous = null)
    {
        $this->code = $error_code;
        parent::__construct($message, $previous);
    }
}
