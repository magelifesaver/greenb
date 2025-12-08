<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/class-aaa-oc-options-loader.php
 * Purpose: Loader for AAA_OC_Options module — registers table installer, helpers, and autoloads option functions.
 * Version: 1.1.0 (adds guarded debug toggle)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local per-file debug flag.
 * To enable globally, define('AAA_OC_OPTIONS_LOADER_DEBUG', true) before this file loads.
 */
if ( ! defined( 'AAA_OC_OPTIONS_LOADER_DEBUG' ) ) {
	define( 'AAA_OC_OPTIONS_LOADER_DEBUG', false );
}

if ( AAA_OC_OPTIONS_LOADER_DEBUG ) {
	error_log( '[AAA_OC_Options_Loader] Initializing Options Module...' );
}

/**
 * Load dependencies.
 */
require_once __DIR__ . '/index/class-aaa-oc-options-table-installer.php';
require_once __DIR__ . '/class-aaa-oc-options.php';
require_once __DIR__ . '/helpers/class-aaa-oc-options-logger.php';
require_once __DIR__ . '/admin/class-aaa-oc-core-settings-page.php';

/**
 * Initialize module.
 */
add_action( 'plugins_loaded', function () {
	if ( class_exists( 'AAA_OC_Options_Table_Installer' ) ) {
		if ( AAA_OC_OPTIONS_LOADER_DEBUG ) {
			error_log( '[AAA_OC_Options_Loader] Running maybe_install()...' );
		}
		AAA_OC_Options_Table_Installer::maybe_install();
	}

	if ( class_exists( 'AAA_OC_Options' ) ) {
		if ( AAA_OC_OPTIONS_LOADER_DEBUG ) {
			error_log( '[AAA_OC_Options_Loader] Booting AAA_OC_Options...' );
		}
		AAA_OC_Options::init();
	}
});

/**
 * Activation hook (table creation)
 */
register_activation_hook(
	dirname( dirname( dirname( __FILE__ ) ) ) . '/aaa-order-workflow.php',
	function () {
		if ( class_exists( 'AAA_OC_Options_Table_Installer' ) ) {
			if ( AAA_OC_OPTIONS_LOADER_DEBUG ) {
				error_log( '[AAA_OC_Options_Loader] Activation hook → maybe_install()' );
			}
			AAA_OC_Options_Table_Installer::maybe_install();
		}
	}
);
