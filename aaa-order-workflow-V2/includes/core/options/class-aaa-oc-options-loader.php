<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/class-aaa-oc-options-loader.php
 * Purpose: Bootstrap the Options subsystem for AAA Order Workflow.
 * Version: 1.3.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* STEP 1: Idempotent guard */
if ( defined( 'AAA_OC_OPTIONS_LOADER_READY' ) ) { return; }
define( 'AAA_OC_OPTIONS_LOADER_READY', true);

/* STEP 2: Debug toggle + helper */
if ( ! defined( 'AAA_OC_OPTIONS_LOADER_DEBUG' ) ) define( 'AAA_OC_OPTIONS_LOADER_DEBUG', false );
if ( ! function_exists( 'aaa_oc_options_loader_debug' ) ) {
	function aaa_oc_options_loader_debug(): bool {
		if ( function_exists( 'aaa_oc_get_option' ) ) {
			return (int) aaa_oc_get_option( 'options_debug', 'modules', 0 ) === 1;
		}
		return (bool) AAA_OC_OPTIONS_LOADER_DEBUG;
	}
}

/* STEP 3: Base paths */
$opt_dir  = __DIR__;
$core_dir = dirname( __DIR__ );
$ver_dir  = $core_dir . '/version';
$mod_dir  = $core_dir . '/modules';
$adm_dir  = $opt_dir  . '/admin';
$idx_dir  = $opt_dir  . '/index';
$hlp_dir  = $opt_dir  . '/helpers';

/* STEP 4: Load central util (silent) */
if ( ! class_exists( 'AAA_OC_Loader_Util' ) && file_exists( $hlp_dir . '/class-aaa-oc-loader-util.php' ) ) {
	require_once $hlp_dir . '/class-aaa-oc-loader-util.php';
}

/* STEP 5: Consistent require helper (tracked when util present) */
if ( ! function_exists( 'aaa_oc_require_or_log' ) ) {
	function aaa_oc_require_or_log( $file ) {
		$file = (string) $file;
		if ( $file === '' ) return false;
		if ( file_exists( $file ) ) {
			require_once $file;
			if ( class_exists('AAA_OC_Loader_Util') ) AAA_OC_Loader_Util::require_or_log( $file, 'options' );
			return true;
		}
		if ( aaa_oc_options_loader_debug() ) error_log( '[AAA_OC_Options_Loader] Missing: ' . $file );
		return false;
	}
}

/* STEP 6: Prereqs for settings/registry UI */
aaa_oc_require_or_log( $ver_dir . '/class-aaa-oc-version.php' );
aaa_oc_require_or_log( $mod_dir . '/class-aaa-oc-modules-registry.php' );

/* STEP 7: Options layer: installer → helpers → API */
aaa_oc_require_or_log( $idx_dir . '/class-aaa-oc-options-table-installer.php' );
aaa_oc_require_or_log( $idx_dir . '/class-aaa-oc-options-table-extender.php' );
aaa_oc_require_or_log( $hlp_dir . '/class-aaa-oc-options-logger.php' );
aaa_oc_require_or_log( $hlp_dir . '/class-aaa-oc-loader-util.php' );
if ( ! function_exists('aaa_oc_get_option') ) {
	aaa_oc_require_or_log( $opt_dir . '/class-aaa-oc-options.php' );
}

/* STEP 8: Admin UI */
if ( is_admin() ) {
	aaa_oc_require_or_log( $adm_dir . '/class-aaa-oc-core-settings-page.php' );
	aaa_oc_require_or_log( $adm_dir . '/class-aaa-oc-tabs-registrar.php' );
}

/* STEP 9: Boot (ensure table + wrappers) */
if ( ! function_exists('aaa_oc_options_loader_boot') ) {
	function aaa_oc_options_loader_boot() {
		static $did = false; if ( $did ) return; $did = true;
		if ( class_exists( 'AAA_OC_Options_Table_Installer' ) ) AAA_OC_Options_Table_Installer::maybe_install();
		if ( class_exists( 'AAA_OC_Options' ) )                AAA_OC_Options::init();
	}
}
add_action( 'plugins_loaded', 'aaa_oc_options_loader_boot', 5 );

/* STEP 10: Activation safety */
$plugin_file_for_hook = defined( 'AAA_OC_PLUGIN_FILE' ) ? AAA_OC_PLUGIN_FILE : dirname( dirname( dirname( __FILE__ ) ) ) . '/aaa-order-workflow.php';
register_activation_hook( $plugin_file_for_hook, function () {
	if ( class_exists( 'AAA_OC_Options_Table_Installer' ) ) AAA_OC_Options_Table_Installer::maybe_install();
});
