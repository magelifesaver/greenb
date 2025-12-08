<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Client HTTP errors enumerator.
 *
 * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
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
    /** @var int conflict error */
    public const CONFLICT = 409;
    /** @link https://en.wikipedia.org/wiki/Hyper_Text_Coffee_Pot_Control_Protocol */
    public const I_AM_A_TEAPOT = 418;
    /** @var int server unable to produce a response */
    public const MISDIRECTED_REQUEST = 421;
    /** @var int request was well-formed but could not be processed by the server */
    public const UNPROCESSABLE_CONTENT = 422;
}
