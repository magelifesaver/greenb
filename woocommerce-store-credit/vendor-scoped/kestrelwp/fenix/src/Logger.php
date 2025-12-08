<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use Error;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Basic_Logger;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Contracts\Logger as Logger_Interface;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Log_Level;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Extension;
use WC_Logger_Interface;
/**
 * Logger.
 *
 * @since 1.0.0
 *
 * @method static void emergency(string $message, string|null $log_id = null, array $context = [])
 * @method static void alert(string $message, string|null $log_id = null, array $context = [])
 * @method static void critical(string $message, string|null $log_id = null, array $context = [])
 * @method static void error(string $message, string|null $log_id = null, array $context = [])
 * @method static void warning(string $message, string|null $log_id = null, array $context = [])
 * @method static void notice(string $message, string|null $log_id = null, array $context = [])
 * @method static void info(string $message, string|null $log_id = null, array $context = [])
 * @method static void debug(string $message, string|null $log_id = null, array $context = [])
 */
final class Logger
{
    /** @var Extension|Plugin|null */
    private static $plugin = null;
    /** @var Logger_Interface|WC_Logger_Interface|null */
    private static $logger = null;
    /**
     * Gets the plugin instance, if available.
     *
     * @return Extension|Plugin|null
     */
    private static function get_plugin(): ?Plugin
    {
        if (self::$plugin) {
            return self::$plugin;
        }
        $plugin = null;
        if (Container::has(Plugin::class)) {
            $plugin = Container::get(Plugin::class);
        }
        if ($plugin instanceof Plugin) {
            self::$plugin = $plugin;
        }
        return $plugin;
    }
    /**
     * Gets the logger instance.
     *
     * @since 1.0.0
     *
     * @return Logger_Interface|WC_Logger_Interface
     */
    private static function get_logger()
    {
        if (self::$logger) {
            return self::$logger;
        }
        $logger = Basic_Logger::instance();
        if ($plugin = self::get_plugin()) {
            $logger = $plugin->config()->get('logger');
            if ($logger && is_string($logger) && (is_a($logger, Logger_Interface::class, \true) || is_a($logger, WC_Logger_Interface::class, \true))) {
                $logger = self::$logger = new $logger();
            } elseif ($plugin instanceof Extension && function_exists('wc_get_logger')) {
                $logger = self::$logger = wc_get_logger();
            }
        }
        return $logger;
    }
    /**
     * Writes data to the WooCommerce Log (e.g.: woocommerce/logs/plugin-id-xxx.txt).
     *
     * @since 1.0.0
     *
     * @param string $message contents to write to log
     * @param string $level severity of log message, see class constants
     * @param string|null $log_id optional log id to segment the files by, defaults to plugin id
     * @param array<mixed> $context optional data to pass to log handler
     * @return void
     */
    private static function log(string $message, string $level = Log_Level::DEBUG, ?string $log_id = null, array $context = []): void
    {
        if (empty($log_id)) {
            $plugin = self::get_plugin();
            $log_id = $plugin ? $plugin->handle() : null;
            if ($log_id) {
                $context = array_merge(['source' => $log_id], $context);
            }
        }
        self::get_logger()->log($level, $message, $context);
    }
    // phpcs:disable
    /**
     * Magic method to support logging directly to a log level.
     *
     * @since 1.0.0
     *
     * @param string $method the method called, must be one of the log levels defined
     * @param array<mixed> $args method arguments provided
     *
     * @phpstan-ignore-next-line
     */
    public static function __callStatic(string $method, array $args = []): void
    {
        if (!in_array($method, Log_Level::values(), \true)) {
            // @phpstan-ignore-next-line
            throw new Error('Call to undefined method: ' . __METHOD__);
        }
        $message = $args[0];
        $log_id = isset($args[1]) && is_string($args[1]) ? $args[1] : null;
        $context = isset($args[2]) && is_array($args[2]) ? $args[2] : [];
        self::log($message, $method, $log_id, $context);
    }
    // phpcs:enable
}
