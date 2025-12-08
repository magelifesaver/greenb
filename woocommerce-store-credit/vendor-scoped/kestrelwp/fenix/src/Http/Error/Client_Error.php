<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Client errors enumerator.
 *
 * @since 1.0.0
 */
final class Client_Error
{
    use Is_Enum;
    /** @var int bad request error */
    public const BAD_REQUEST = 400;
    /** @var int unauthorized request error */
    public const UNAUTHORIZED = 401;
    /** @var int forbidden request error */
    public const FORBIDDEN = 403;
    /** @var int not found error */
    public const NOT_FOUND = 404;
    /** @var int http method not allowed error */
    public const METHOD_NOT_ALLOWED = 405;
    /** @var int http headers not acceptable error */
    public const NOT_ACCEPTABLE = 406;
    /** @var int request timeout error */
    public const REQUEST_TIMEOUT = 408;
}
