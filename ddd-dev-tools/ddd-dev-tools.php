<?php
/**
 * Plugin Name: DDD Dev Tools
 * Plugin URI:  https://example.com/ddd-dev-tools
 * Description: Dev/staging toolkit (enable only when needed): URL Cleaner, Pagination Redirect, Wrong-site Click Logger, Troubleshooter Search, ATUM Logs viewer, and Debuggers (order/product/debug.log). Includes one Settings page with tabs, optional debug logging, and log rotation in uploads/ddd-dev-tools/logs.
 * Version:     2.1.0
 * Author:      My Frrenn
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * This plugin is intended for development and staging. On production sites: enable, use, then disable.
 */

defined( 'ABSPATH' ) || exit;

define( 'DDD_DT_VERSION', '2.1.0' );
define( 'DDD_DT_FILE', __FILE__ );
define( 'DDD_DT_DIR', plugin_dir_path( __FILE__ ) );
define( 'DDD_DT_URL', plugin_dir_url( __FILE__ ) );

require_once DDD_DT_DIR . 'includes/bootstrap.php';

register_activation_hook( __FILE__, [ 'DDD_DT_Bootstrap', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'DDD_DT_Bootstrap', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'DDD_DT_Bootstrap', 'init' ] );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $url = admin_url( 'tools.php?page=ddd-dev-tools' );
    $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ddd-dev-tools' ) . '</a>';
    return $links;
} );
