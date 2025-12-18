<?php
/**
 * Plugin Name:       DDD Dev Debug Manager
 * Plugin URI:        https://example.com/
 * Description:       Provides a WordPress admin tool for viewing and downloading the current debug.log file.  A live tail view lets you watch new log entries in real‑time, the download tool creates a zip archive split into manageable pieces, and snapshot tools let you generate a one‑off copy of the log (with optional duplicate filtering) or clear the cached snapshot. Version 1.2.0 introduces the ability to clear the debug.log file from the interface and an optional duplicate filter for the live tail output.
 * Version:           1.2.0
 * Author:            webmaster
 * License:           GPL‑2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ddd-dev-debug-manager
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define basic plugin constants. These are used in multiple places and bumping
// the version here ensures cache busting for assets.
if ( ! defined( 'DDD_DEBUG_MANAGER_VERSION' ) ) {
    define( 'DDD_DEBUG_MANAGER_VERSION', '1.2.0' );
}
if ( ! defined( 'DDD_DEBUG_MANAGER_DIR' ) ) {
    define( 'DDD_DEBUG_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'DDD_DEBUG_MANAGER_URL' ) ) {
    define( 'DDD_DEBUG_MANAGER_URL', plugin_dir_url( __FILE__ ) );
}

// Include the main plugin loader. This file conditionally loads admin
// functionality and registers hooks common to both front‑end and admin areas.
require_once DDD_DEBUG_MANAGER_DIR . 'includes/class-ddd-dev-debug-manager.php';