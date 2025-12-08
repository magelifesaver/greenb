<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Exceptions;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Exceptions\Exception;
use Throwable;
/**
 * Base class for HTTP exceptions.
 *
 * @since 1.7.1
 */
abstract class Http_Exception extends Exception
{
    /**
     * Constructor.
     *
     * @since 1.7.1
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
