<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Currency;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Plugins;
use WP_Post;
/**
 * WooCommerce repository class.
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
     * Determines if WooCommerce is in coming soon mode.
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public static function is_coming_soon(): bool
    {
        // does not check if WooCommerce is active, as the admin may have temporarily deactivated the plugin
        return 'yes' === get_option('woocommerce_coming_soon');
    }
    /**
     * Determines if WooCommerce is in live mode.
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public static function is_live(): bool
    {
        return self::is_active() && 'yes' !== get_option('woocommerce_coming_soon');
    }
    /**
     * Returns the WooCommerce currency as an object.
     *
     * @since 1.6.0
     *
     * @return Currency
     */
    public static function currency(): Currency
    {
        // @phpstan-ignore-next-line the default currency is always set in WooCommerce and this wouldn't throw an exception
        return new Currency(get_woocommerce_currency());
    }
    /**
     * Returns the rounding precision utilized by WooCommerce.
     *
     * @since 1.7.0
     *
     * @return int
     */
    public static function rounding_precision(): int
    {
        return intval(wc_get_rounding_precision());
    }
    /**
     * Returns the raw WooCommerce locale data.
     *
     * @since 1.7.0
     *
     * @return array<string, array<string, array<string, string>|int|string>>
     *
     * @phpstan-return array<string, array{
     *     currency_code: string,
     *     currency_pos: string,
     *     thousand_sep: string,
     *     decimal_sep: string,
     *     num_decimals: int,
     *     weight_unit: string,
     *     dimension_unit: string,
     *     direction: string,
     *     default_locale: string,
     *     name: string,
     *     singular: string,
     *     plural: string,
     *     short_symbol: string,
     *     locales: array<string, array{
     *          thousand_sep: string,
     *          decimal_sep: string,
     *          direction: string,
     *          currency_pos: string,
     *     }>,
     * }>
     */
    public static function locale_data(): array
    {
        return include self::path('i18n/locale-info.php', \true);
        // @phpstan-ignore-line
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
     * Determines if the checkout page is using the checkout block.
     *
     * @since 1.6.0
     *
     * @param int|string|WP_Post $content when checking a specific post content, pass the post ID, slug, or WP_Post object
     * @return bool
     */
    public static function is_using_checkout_block($content = null): bool
    {
        if (null !== $content) {
            return has_block('woocommerce/checkout', $content);
        }
        return class_exists(CartCheckoutUtils::class) && is_callable(CartCheckoutUtils::class . '::is_checkout_block_default') && CartCheckoutUtils::is_checkout_block_default();
    }
    /**
     * Determines if the cart page is using the cart block.
     *
     * @since 1.6.0
     *
     * @param int|string|WP_Post $content when checking a specific post content, pass the post ID, slug, or WP_Post object
     * @return bool
     */
    public static function is_using_cart_block($content = null): bool
    {
        if (null !== $content) {
            return has_block('woocommerce/cart', $content);
        }
        return class_exists(CartCheckoutUtils::class) && is_callable(CartCheckoutUtils::class . '::is_cart_block_default') && CartCheckoutUtils::is_cart_block_default();
    }
    /**
     * Returns the URL to the WooCommerce logs page in the admin area.
     *
     * @since 1.6.0
     *
     * @param "action-scheduler"|"logs"|"status"|"tools"|string $tab the tab to link to
     * @return string
     */
    public static function admin_status_url(string $tab = 'status'): string
    {
        _deprecated_function(__METHOD__, '1.7.0', 'Kestrel\Fenix\WooCommerce\Admin::status_url()');
        return WooCommerce\Admin::status_url($tab);
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
        $woocommerce_path = WC()->plugin_path();
        if (!$absolute) {
            $woocommerce_path = plugin_basename($woocommerce_path);
        }
        if (!empty($path)) {
            $woocommerce_path = trailingslashit($woocommerce_path) . ltrim($path, '/');
        }
        return $woocommerce_path;
    }
}
