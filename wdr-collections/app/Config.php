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

namespace WDR_COL\App;

defined('ABSPATH') or exit;

class Config
{
    /**
     * Get config
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = false)
    {
        if (empty($key)) {
            return false;
        }

        $config = require WDR_COL_PLUGIN_PATH . '/config.php';
        if (array_key_exists($key, $config)) {
            return $config[$key];
        } else if (strpos($key, '.') !== false) {
            foreach (explode('.', $key) as $index) {
                if (!is_array($config) || !array_key_exists($index, $config)) {
                    return $default;
                }
                $config = &$config[$index];
            }
            return $config;
        } else {
            $key = sanitize_key($key);
            if (empty($key)) {
                return false;
            }

            $key = self::get('plugin.prefix', 'wdr_col_') . $key;
            return get_option($key, $default);
        }
    }

    /**
     * Set config
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set($key, $value)
    {
        $key = sanitize_key($key);
        if (empty($key)) {
            return false;
        }

        $key = self::get('plugin.prefix', 'wdr_col_') . $key;
        return update_option($key, $value);
    }
}