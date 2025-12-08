<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package           wdr-collections
 * @author            Anantharaj B <anantharaj@flycart.org>
 * @copyright         2022 Flycart
 * @license           GPL-3.0-or-later
 * @link              https://flycart.org
 *
 * @wordpress-plugin
 * Plugin Name:       Discount Rules: Collections
 * Plugin URI:        https://flycart.org
 * Description:       Add-on for Woo Discount Rules
 * Version:           1.2.2
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Flycart
 * Author URI:        https://flycart.org
 * Text Domain:       wdr-collections
 * Domain Path:       /i18n/languages
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or exit;

defined('WDR_COL_PLUGIN_FILE') or define('WDR_COL_PLUGIN_FILE', __FILE__);
defined('WDR_COL_PLUGIN_PATH') or define('WDR_COL_PLUGIN_PATH', plugin_dir_path(__FILE__));

// To load PSR4 autoloader
if (file_exists(WDR_COL_PLUGIN_PATH . '/vendor/autoload.php')) {
    require WDR_COL_PLUGIN_PATH . '/vendor/autoload.php';
} else {
    wp_die('Woo Discount Rules: Collections is unable to find the autoload file.');
}

// To bootstrap the plugin
if (class_exists('WDR_COL\App\Core')) {
    global $wdr_col_app;
    $wdr_col_app = WDR_COL\App\Core::instance();
    $wdr_col_app->bootstrap(); // start plugin
} else {
    wp_die(__('Woo Discount Rules: Collections is unable to find the Core class.', 'wdr-collections'));
}
