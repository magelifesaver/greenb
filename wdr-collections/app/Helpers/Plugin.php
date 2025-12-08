<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App\Helpers;

use WDR_COL\App\Config;

defined('ABSPATH') or exit;

class Plugin
{
    /**
     * Active plugins
     *
     * @var array
     */
    public static $active_plugins;

    /**
     * Check dependencies
     *
     * @param bool $die
     * @return bool
     */
    public static function checkDependencies($die = false)
    {
        global $wp_version;

        // check php version
        $php_version = Config::get('require.php', '*');
        if (!Functions::checkVersion(PHP_VERSION, $php_version)) {
            $message = sprintf(__('Woo Discount Rules: Collections requires PHP version %s', 'wdr-collections'), $php_version);
            $die ? die($message) : WP::adminNotice($message, 'error');
            return false;
        }

        // check wordpress version
        $wordpress_version = Config::get('require.wordpress', '*');
        if (!Functions::checkVersion($wp_version, $wordpress_version)) {
            $message = sprintf(__('Woo Discount Rules: Collections requires WordPress version %s', 'wdr-collections'), $wordpress_version);
            $die ? die($message) : WP::adminNotice($message, 'error');
            return false;
        }

        // check required plugins and its version
        $required_plugins = Config::get('require.plugins', []);
        foreach ($required_plugins as $plugin) {
            if (!isset($plugin['name']) || !isset($plugin['file'])) {
                continue;
            }

            $plugin_name = $plugin['name'];
            if (isset($plugin['url'])) {
                $plugin_name = '<a href="' . $plugin['url'] . '" target="_blank">' . $plugin_name . '</a>';
            }

            // check plugin is active
            if (!self::isActive($plugin['file'])) {
                $message = sprintf(__('Woo Discount Rules: Collections requires %s plugin to be installed and active', 'wdr-collections'), $plugin_name);
                $die ? die($message) : WP::adminNotice($message, 'error');
                return false;
            }

            // check plugin version is satisfied
            if (!empty($plugin['file'])) {
                $plugin_version = self::getVersion($plugin['file']);
                if (!empty($plugin_version) && !Functions::checkVersion($plugin_version, $plugin['version'])) {
                    $message = sprintf(__('Woo Discount Rules: Collections requires %s version %s', 'wdr-collections'), $plugin_name, $plugin['version']);
                    $die ? die($message) : WP::adminNotice($message, 'error');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get all active plugins
     *
     * @return array
     */
    public static function activePlugins()
    {
        if (!isset(self::$active_plugins)) {
            $active_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
            if (is_multisite()) {
                $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
            }
            self::$active_plugins = $active_plugins;
        }
        return self::$active_plugins;
    }

    /**
     * Check if the plugin is active or not
     *
     * @param string $file
     * @return bool
     */
    public static function isActive($file)
    {
        $active_plugins = self::activePlugins();
        return in_array($file, $active_plugins) || array_key_exists($file, $active_plugins);
    }

    /**
     * Get plugin data
     *
     * @param string $file
     * @return array
     */
    public static function getData($file)
    {
        $plugin_file = ABSPATH . 'wp-content/plugins/' . $file;
        if (file_exists($plugin_file) && function_exists('get_plugin_data')) {
            return get_plugin_data($plugin_file);
        }
        return [];
    }

    /**
     * Get plugin version
     *
     * @param string $file
     * @return string|null
     */
    public static function getVersion($file)
    {
        $data = self::getData($file);
        return isset($data['Version']) ? $data['Version'] : null;
    }
}