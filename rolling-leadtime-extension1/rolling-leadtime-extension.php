<?php
/**
 * Plugin Name: Rolling Lead‑Time Extension
 * Description: Adds rolling lead‑time logic to Time Picker for WooCommerce. Computes the earliest allowable delivery/pickup slot based on preparation time, dispatch buffer and travel time and adjusts the existing time picker accordingly. Also displays an estimated arrival window on product pages.
 * Version: 1.0.0
 * Author: AI Assistant
 * License: GPL2
 * Text Domain: rlt
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
if (!defined('RLT_PLUGIN_DIR')) {
    define('RLT_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('RLT_PLUGIN_URL')) {
    define('RLT_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Include required class files.
require_once RLT_PLUGIN_DIR . 'includes/class-rlt-settings.php';
require_once RLT_PLUGIN_DIR . 'includes/class-rlt-core.php';

/**
 * Bootstraps the plugin components.
 */
function rlt_init_plugin() {
    // Load settings manager.
    RLT_Settings::instance();
    // Load core functionality.
    RLT_Core::instance();
}
add_action('plugins_loaded', 'rlt_init_plugin');
