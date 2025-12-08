<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress\Plugins;
/**
 * WooCommerce helper.
 *
 * For additional helper classes, see the `WooCommerce` namespace.
 *
 * @since 1.1.0
 */
final class WooCommerce
{
    /**
     * Gets the installed WooCommerce version.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public static function version(): ?string
    {
        return defined('WC_VERSION') ? \WC_VERSION : null;
    }
    /**
     * Determines if the installed WooCommerce version matches the specified version and comparator.
     *
     * @since 1.0.0
     *
     * @param string $comparator
     * @param string $version
     * @return bool
     */
    public static function is_version(string $comparator, string $version): bool
    {
        $installed_version = self::version();
        return $installed_version && version_compare($installed_version, $version, $comparator);
    }
    /**
     * Determines whether WooCommerce is active in the current WordPress installation.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public static function is_active(): bool
    {
        return Plugins::is_woocommerce_active();
    }
    /**
     * Determines if the high performance order tables feature is enabled.
     *
     * @since 1.2.0
     *
     * @return bool
     */
    public static function are_custom_order_tables_enabled(): bool
    {
        return class_exists(OrderUtil::class) && is_callable(OrderUtil::class . '::custom_orders_table_usage_is_enabled') && OrderUtil::custom_orders_table_usage_is_enabled();
    }
    /**
     * Returns the path to the WooCommerce plugin directory in the current installation.
     *
     * If $absolute, e.g. /var/www/html/wp-content/plugins/woocommerce/$path
     * If relative, e.g. woocommerce/$path
     *
     * @since 1.2.1
     *
     * @param string $path path to append to the WooCommerce path in the plugin directory
     * @param bool $absolute optional, default false
     * @return string empty string if WooCommerce is not active
     */
    public static function path(string $path = '', bool $absolute = \false): string
    {
        if (!self::is_active()) {
            return '';
        }
        $woocommerce_path = wc()->plugin_path();
        if (!$absolute) {
            $woocommerce_path = plugin_basename($woocommerce_path);
        }
        if (!empty($path)) {
            $woocommerce_path = trailingslashit($woocommerce_path) . ltrim($path, '/');
        }
        return $woocommerce_path;
    }
}
