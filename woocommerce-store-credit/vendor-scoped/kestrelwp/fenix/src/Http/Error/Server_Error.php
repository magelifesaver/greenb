<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Error;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Server errors enumerator.
 *
 * @since 1.0.0
 */
final class Server_Error
{
    use Is_Enum;
    /** @var int internal server error */
    public const INTERNAL_SERVER_ERROR = 500;
    /** @var int not implemented error */
    public const NOT_IMPLEMENTED = 501;
    /** @var int bad gateway error */
    public const BAD_GATEWAY = 502;
    /** @var int service unavailable error */
    public const SERVICE_UNAVAILABLE = 503;
    /** @var int gateway timeout error */
    public const GATEWAY_TIMEOUT = 504;
    /** @var int http version not supported error */
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
}
