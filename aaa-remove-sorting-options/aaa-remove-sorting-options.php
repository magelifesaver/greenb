<?php
/**
 * Plugin Name: AAA Remove Sorting Options
 * Description: Provides a configurable interface for controlling WooCommerce catalog sorting options per page type. You can enable or disable the sorting dropdown on the shop, search results, product categories, tags, attributes, and brand archives. For each page type you can choose which sort options are available and set a default ordering.
 * Version: 2.0.0
 * Author: Workflow Delivery
 * License: GPL-2.0-or-later
 *
 * This main file loads the core functionality and the admin settings panel. The plugin follows
 * a wide‑and‑thin architecture: each file is kept under 150 lines and grouped by purpose. Only
 * loader-level logic lives here; all functionality is delegated to files in the includes folder.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define a constant for the plugin path so included files can reliably locate resources.
if ( ! defined( 'AAA_RSO_PATH' ) ) {
    define( 'AAA_RSO_PATH', plugin_dir_path( __FILE__ ) );
}

// Load helper functions first as they are required by both core and admin.
require_once AAA_RSO_PATH . 'includes/helpers.php';

// Load the core functionality that applies filtering logic on the front end.
require_once AAA_RSO_PATH . 'includes/core.php';

// Load the admin settings interface only in the dashboard.
if ( is_admin() ) {
    // Split admin functionality into separate files for settings registration and page markup.
    require_once AAA_RSO_PATH . 'includes/settings-register.php';
    require_once AAA_RSO_PATH . 'includes/settings-page.php';
}