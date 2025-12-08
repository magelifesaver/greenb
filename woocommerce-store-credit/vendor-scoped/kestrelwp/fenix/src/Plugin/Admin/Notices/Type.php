<?php

namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Defines the notice types.
 *
 * @since 1.0.0
 */
final class Type
{
    use Is_Enum;
    /** @var string */
    public const ERROR = 'error';
    /** @var string */
    public const WARNING = 'warning';
    /** @var string */
    public const SUCCESS = 'success';
    /** @var string */
    public const INFO = 'info';
}
