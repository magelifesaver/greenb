<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/aaa-product-ai-manager.php
 * Plugin Name: AAA OC Product AI Manager
 * Description: Part 1 — Create Attribute Sets linked to a product category (no terms/AI yet).
 * Version: 0.2.3
 * Author: Webmaster Workflow Delivery
 * Text Domain: aaa-paim
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ------------------------------------------------------------
 * Plugin constants
 * ------------------------------------------------------------
 * NOTE: Set AAA_PAIM_DEBUG to true to enable plugin logs
 * regardless of WP_DEBUG. When false, PAIM logs are muted.
 */
define( 'AAA_PAIM_VERSION', '0.2.3' );
define( 'AAA_PAIM_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_PAIM_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'AAA_PAIM_DEBUG' ) ) {
	// Default OFF to silence logs; override via wp-config or filter.
	define( 'AAA_PAIM_DEBUG', false );
}

/**
 * Centralized logger (respects both WP_DEBUG and AAA_PAIM_DEBUG filter)
 */
if ( ! function_exists( 'aaa_paim_log' ) ) {
	function aaa_paim_log( $message, $context = 'MAIN' ) {
		$enabled = (bool) apply_filters( 'aaa_paim_debug', AAA_PAIM_DEBUG );
		if ( $enabled && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				$message = wp_json_encode( $message );
			}
			error_log( sprintf( ' [AAA-PAIM][%s] %s', $context, $message ) );
		}
	}
}

/**
 * Bootstrap
 */
aaa_paim_log( 'Plugin bootstrap loaded v' . AAA_PAIM_VERSION );

require_once AAA_PAIM_DIR . 'aaa-paim-loader.php';

/**
 * Activation: ensure installers run (single-site or network)
 */
function aaa_paim_activate_plugin( $network_wide ) {
	aaa_paim_log( 'Activation start' );

	try {
		require_once AAA_PAIM_DIR . 'index/class-aaa-paim-table-installer.php';
		require_once AAA_PAIM_DIR . 'index/class-aaa-paim-ai-flag-table-installer.php';

		if ( ! function_exists( 'aaa_paim_install_for_blog' ) ) {
			function aaa_paim_install_for_blog( $blog_id = null ) {
				if ( is_multisite() && $blog_id !== null ) {
					switch_to_blog( (int) $blog_id );
				}

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';

				if ( class_exists( 'AAA_Paim_Table_Installer' ) ) {
					if ( method_exists( 'AAA_Paim_Table_Installer', 'maybe_install' ) ) {
						AAA_Paim_Table_Installer::maybe_install();
					} else {
						AAA_Paim_Table_Installer::install();
					}
				}

				if ( class_exists( 'AAA_PAIM_AI_Flag_Table_Installer' ) ) {
					if ( method_exists( 'AAA_PAIM_AI_Flag_Table_Installer', 'maybe_install' ) ) {
						AAA_PAIM_AI_Flag_Table_Installer::maybe_install();
					} else {
						AAA_PAIM_AI_Flag_Table_Installer::install();
					}
				}

				if ( is_multisite() && $blog_id !== null ) {
					restore_current_blog();
				}
			}
		}

		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $sid ) {
				aaa_paim_install_for_blog( $sid );
			}
		} else {
			aaa_paim_install_for_blog( null );
		}

	} catch ( Throwable $e ) {
		aaa_paim_log( 'Activation exception: ' . $e->getMessage() );
		wp_die( 'PAIM activation failed while creating tables. Check PHP error log for details.' );
	}

	aaa_paim_log( 'Activation end' );
}
register_activation_hook( __FILE__, 'aaa_paim_activate_plugin' );

/**
 * Optional marker for load order
 */
add_action( 'plugins_loaded', function () {
	aaa_paim_log( 'plugins_loaded' );
}, 1);

