<?php
/**
 * Plugin Name: DDD Block User IP (XHV98-DEV)
 * Description: Block specified IP addresses and optionally auto‑block non‑local countries, with basic IP hit logging and safelist support.
 * Version:     1.6.1
 * Author:      My Frrenn
 *
 * This is the bootstrap file for the DDD Block User IP plugin. It defines core constants,
 * loads supporting modules and registers activation hooks. All of the business logic is
 * implemented in the files under the `includes` directory to keep this loader file
 * concise and easier to understand. The wide‑and‑thin architecture means each module
 * remains under 150 lines for easier auditing and maintenance.
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin version for cache busting and migrations.
if ( ! defined( 'DDD_BUIP_VERSION' ) ) {
define( 'DDD_BUIP_VERSION', '1.6.0' );
}

// Directory constants.
if ( ! defined( 'DDD_BUIP_DIR' ) ) {
    define( 'DDD_BUIP_DIR', plugin_dir_path( __FILE__ ) );
}

// Debug flag. Set this to true in wp-config.php to enable debug logging.
if ( ! defined( 'DDD_BUIP_DEBUG' ) ) {
    define( 'DDD_BUIP_DEBUG', false );
}

// Include helper functions and modules. Each include file is responsible for a
// distinct area of functionality (helpers, logging, admin UI, blocking). Keeping
// concerns separated makes it easier to test and extend in the future.
require_once DDD_BUIP_DIR . 'includes/helpers.php';
require_once DDD_BUIP_DIR . 'includes/log.php';
require_once DDD_BUIP_DIR . 'includes/admin-menu.php';
require_once DDD_BUIP_DIR . 'includes/admin-page.php';
require_once DDD_BUIP_DIR . 'includes/blocker.php';
require_once DDD_BUIP_DIR . 'includes/ip-table.php';

// Register the activation hook. The hook must be registered in the main plugin
// file to ensure it runs when the plugin is activated. It calls the
// installation function defined in includes/log.php to create the custom
// database table.
register_activation_hook( __FILE__, 'ddd_buip_install' );
