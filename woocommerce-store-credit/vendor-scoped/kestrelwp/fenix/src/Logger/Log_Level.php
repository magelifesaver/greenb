<?php

namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Log levels.
 *
 * @since 1.0.0
 */
final class Log_Level
{
    use Is_Enum;
    /** @var string emergency log level */
    public const EMERGENCY = 'emergency';
    /** @var string alert log level */
    public const ALERT = 'alert';
    /** @var string critical log level */
    public const CRITICAL = 'critical';
    /** @var string error log level */
    public const ERROR = 'error';
    /** @var string warning log level */
    public const WARNING = 'warning';
    /** @var string notice log level */
    public const NOTICE = 'notice';
    /** @var string info log level */
    public const INFO = 'info';
    /** @var string debug log level */
    public const DEBUG = 'debug';
}
