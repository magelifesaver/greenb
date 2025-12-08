<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * HTTP methods.
 *
 * @since 1.0.0
 */
final class Method
{
    use Is_Enum;
    /** @var string */
    public const GET = 'GET';
    /** @var string */
    public const POST = 'POST';
    /** @var string */
    public const PUT = 'PUT';
    /** @var string */
    public const DELETE = 'DELETE';
    /** @var string */
    public const PATCH = 'PATCH';
    /** @var string */
    public const HEAD = 'HEAD';
    /** @var string */
    public const OPTIONS = 'OPTIONS';
    /** @var string */
    public const TRACE = 'TRACE';
}
