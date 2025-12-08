<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger as Main_Logger;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Contracts\Logger;
use WC_Logger;
/**
 * Basic logger.
 *
 * This logger will be loaded if no other logger is provided and {@see WC_Logger} is not available.
 *
 * @see Main_Logger::get_logger()
 *
 * @since 1.0.0
 */
class Basic_Logger implements Logger
{
    /**
     * Returns an instance of the logger.
     *
     * @return Basic_Logger
     */
    public static function instance(): Basic_Logger
    {
        return new self();
    }
    /**
     * Writes a message to the general log.
     *
     * @since 1.0.0
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // phpcs:ignore
        error_log(
            // phpcs:ignore
            print_r(['level' => $level, 'message' => $message, 'context' => $context], \true)
        );
    }
}
