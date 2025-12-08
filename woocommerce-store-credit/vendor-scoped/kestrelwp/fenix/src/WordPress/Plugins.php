<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Cache;
defined('ABSPATH') or exit;
/**
 * WordPress plugins handler.
 *
 * @since 1.0.0
 */
final class Plugins
{
    /**
     * Returns the plugin data for a given plugin file.
     *
     * @see \get_plugin_data() but this also adds caching
     *
     * @since 1.1.0
     *
     * @param string $plugin_file absolute path to the plugin file
     * @return array<string, string>
     */
    public static function get_plugin_data(string $plugin_file): array
    {
        $plugin_name = plugin_basename($plugin_file);
        $plugin_data = Cache::key($plugin_name)->remember(function () use ($plugin_file) {
            return get_plugin_data($plugin_file);
        }, \false);
        return is_array($plugin_data) ? $plugin_data : [];
    }
    /**
     * Determines whether a plugin is active in the current WordPress installation.
     *
     * @since 1.0.0
     *
     * @param string $plugin_name
     * @return bool
     */
    public static function is_plugin_active(string $plugin_name): bool
    {
        if (!function_exists('is_plugin_active') && file_exists(\ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once \ABSPATH . 'wp-admin/includes/plugin.php';
        }
        // @NOTE at this point it's very unlikely this won't exist, and we can probably remove the else condition
        if (function_exists('is_plugin_active')) {
            $is_active = is_plugin_active($plugin_name);
        } else {
            $active_plugins = (array) get_option('active_plugins', []);
            if (function_exists('is_multisite') && is_multisite()) {
                $active_plugins = array_merge($active_plugins, array_keys(get_site_option('active_sitewide_plugins', [])));
            }
            $is_active = in_array($plugin_name, $active_plugins, \true);
        }
        return $is_active;
    }
    /**
     * Determines if a plugin is in the process of being activated.
     *
     * @since 1.0.0
     *
     * @param string $plugin_name
     * @return bool
     */
    public static function is_plugin_being_activated(string $plugin_name): bool
    {
        $is_activating = \false;
        // phpcs:ignore
        $is_activation_request = isset($_REQUEST['action'], $_REQUEST['_wpnonce']);
        // check if the plugin is in the process of being activated via the Admin > Plugins screen
        if ($is_activation_request && function_exists('current_user_can') && current_user_can('activate_plugin', $plugin_name)) {
            $action = sanitize_text_field(wp_unslash($_REQUEST['action']));
            $plugin = '';
            // when multiple plugins are being activated at once the $_REQUEST['checked'] is an array of plugin slugs
            if ('activate-selected' === $action) {
                // phpcs:ignore
                if (isset($_REQUEST['checked']) && is_array($_REQUEST['checked']) && in_array($plugin_name, $_REQUEST['checked'], \true) && wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), 'bulk-plugins')) {
                    $plugin = $plugin_name;
                }
            } elseif (in_array($action, ['activate', 'activate-plugin'], \true)) {
                // phpcs:ignore
                if (isset($_REQUEST['plugin']) && wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "activate-plugin_{$plugin_name}")) {
                    // phpcs:ignore
                    $plugin = sanitize_text_field(wp_unslash($_REQUEST['plugin']));
                }
            }
            $is_activating = !empty($plugin) && $plugin_name === $plugin;
            // @phpstan-ignore-next-line
        } elseif (defined('Kestrel\Store_Credit\Scoped\WP_CLI') && \Kestrel\Store_Credit\Scoped\WP_CLI && isset($GLOBALS['argv'])) {
            $expected_arguments = ['plugin', 'activate', $plugin_name];
            $is_activating = array_intersect($expected_arguments, $GLOBALS['argv']) === $expected_arguments;
        }
        return $is_activating;
    }
    /**
     * Determines whether the WooCommerce plugin is active in the current WordPress installation.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function is_woocommerce_active(): bool
    {
        return self::is_plugin_active('woocommerce/woocommerce.php');
    }
}
