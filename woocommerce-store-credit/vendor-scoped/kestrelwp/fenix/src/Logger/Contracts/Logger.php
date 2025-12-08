<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Contracts;

defined('ABSPATH') or exit;
/**
 * Logger interface.
 *
 * @since 1.0.0
 */
interface Logger
{
    /**
     * Add a log entry.
     *
     * @since 1.0.0
     *
     * @param "alert"|"critical"|"debug"|"emergency"|"error"|"info"|"notice"|"warning" $level
     * @param string $message the log message
     * @param array<string, mixed> $context optional additional information for log handlers
     */
    public function log(string $level, string $message, array $context = []): void;
}
