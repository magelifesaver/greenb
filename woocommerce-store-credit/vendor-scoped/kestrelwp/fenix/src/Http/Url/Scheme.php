<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Url;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * URL schemes.
 *
 * @since 1.0.0
 */
final class Scheme
{
    use Is_Enum;
    /** @var string */
    public const HTTP = 'http';
    /** @var string */
    public const HTTPS = 'https';
}
