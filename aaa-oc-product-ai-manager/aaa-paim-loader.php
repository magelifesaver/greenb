<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/aaa-paim-loader.php
 * Purpose: Main PHP loader — includes classes, routes admin, activation.
 * Version: 0.2.3
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ------------------------------------------------------------------------
 * Boot guard: prevent duplicate includes / re-entry
 * --------------------------------------------------------------------- */
if ( defined( 'AAA_PAIM_LOADER_BOOTED' ) ) { return; }
define( 'AAA_PAIM_LOADER_BOOTED', true );

/** ------------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------- */
if ( ! defined( 'AAA_PAIM_DEBUG_LOADER' ) ) { define( 'AAA_PAIM_DEBUG_LOADER', true ); }
if ( ! defined( 'AAA_PAIM_DEBUG' ) ) { define( 'AAA_PAIM_DEBUG', true ); }
if ( ! defined( 'AAA_PAIM_DIR' ) ) { define( 'AAA_PAIM_DIR', plugin_dir_path( __FILE__ ) ); }
if ( ! defined( 'AAA_PAIM_URL' ) ) { define( 'AAA_PAIM_URL', plugin_dir_url( __FILE__ ) ); }
if ( ! defined( 'AAA_PAIM_VERSION' ) ) { define( 'AAA_PAIM_VERSION', '0.2.3' ); }

/** ------------------------------------------------------------------------
 * Logger (honors Global Debug option + WP_DEBUG + per-file constants)
 * --------------------------------------------------------------------- */
if ( ! function_exists( 'aaa_paim_log' ) ) {
	function aaa_paim_log( $msg, $file_key = 'GEN' ) {
		$opt_on = false;
		if ( class_exists( 'AAA_Paim_Options' ) ) {
			$opt_on = (bool) AAA_Paim_Options::get( 'debug', 1 );
		}
		$global = defined( 'AAA_PAIM_DEBUG' ) ? AAA_PAIM_DEBUG : true;
		$local  = defined( 'AAA_PAIM_DEBUG_' . $file_key ) ? constant( 'AAA_PAIM_DEBUG_' . $file_key ) : true;

		if ( $opt_on && $global && $local && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$out = is_scalar( $msg ) ? $msg : wp_json_encode( $msg );
			error_log( '[AAA-PAIM][' . $file_key . '] ' . $out );
		}
	}
}

/** ------------------------------------------------------------------------
 * Loader
 * --------------------------------------------------------------------- */
class AAA_Paim_Loader {

	public static function init() {
		self::includes();

		// Ensure DB tables exist, but only install when needed
		if ( class_exists( 'AAA_Paim_Table_Installer' ) ) {
			if ( method_exists( 'AAA_Paim_Table_Installer', 'maybe_install' ) ) {
				AAA_Paim_Table_Installer::maybe_install();
			} else {
				AAA_Paim_Table_Installer::install();
			}
			aaa_paim_log( 'Base installer invoked', 'LOADER' );
		}
		if ( class_exists( 'AAA_PAIM_AI_Flag_Table_Installer' ) ) {
			if ( method_exists( 'AAA_PAIM_AI_Flag_Table_Installer', 'maybe_install' ) ) {
				AAA_PAIM_AI_Flag_Table_Installer::maybe_install();
			} else {
				AAA_PAIM_AI_Flag_Table_Installer::install();
			}
			aaa_paim_log( 'AI-flag installer invoked', 'LOADER' );
		}

		// Record top-level migrated version once per release (future cross-table migrations go here)
		$last = get_option( 'aaa_paim_migrated_version', '' );
		if ( version_compare( (string) $last, AAA_PAIM_VERSION, '<' ) ) {
			update_option( 'aaa_paim_migrated_version', AAA_PAIM_VERSION, false );
			aaa_paim_log( 'Migrations complete for ' . AAA_PAIM_VERSION, 'LOADER' );
		}

		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		aaa_paim_log( 'Loader init v' . AAA_PAIM_VERSION, 'LOADER' );
	}

	/**
	 * Called from register_activation_hook in the main plugin file.
	 */
	public static function on_activation() {
		require_once AAA_PAIM_DIR . 'index/class-aaa-paim-table-installer.php';
		require_once AAA_PAIM_DIR . 'index/class-aaa-paim-ai-flag-table-installer.php';

		if ( class_exists( 'AAA_Paim_Table_Installer' ) ) {
			if ( method_exists( 'AAA_Paim_Table_Installer', 'maybe_install' ) ) {
				AAA_Paim_Table_Installer::maybe_install();
			} else {
				AAA_Paim_Table_Installer::install();
			}
			aaa_paim_log( 'Activation: base installer ran', 'LOADER' );
		}
		if ( class_exists( 'AAA_PAIM_AI_Flag_Table_Installer' ) ) {
			if ( method_exists( 'AAA_PAIM_AI_Flag_Table_Installer', 'maybe_install' ) ) {
				AAA_PAIM_AI_Flag_Table_Installer::maybe_install();
			} else {
				AAA_PAIM_AI_Flag_Table_Installer::install();
			}
			aaa_paim_log( 'Activation: AI-flag installer ran', 'LOADER' );
		}
		aaa_paim_log( 'Activation installers complete', 'LOADER' );
	}

	private static function includes() {
		// ---- Index / DB installers
		require_once AAA_PAIM_DIR . 'index/class-aaa-paim-table-installer.php';
		require_once AAA_PAIM_DIR . 'index/class-aaa-paim-ai-flag-table-installer.php';

		// ---- Root assets loader (admin CSS/JS)
		require_once AAA_PAIM_DIR . 'aaa-paim-assets-loader.php';
		aaa_paim_log( 'Assets loader included', 'LOADER' );

		// ---- Core inc helpers
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-attribute-registry.php';
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-sets.php';
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-options.php';
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-product.php';
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-ai.php';
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-search.php';
		require_once AAA_PAIM_DIR . 'inc/class-aaa-paim-attrmeta.php';
		aaa_paim_log( 'Core helpers included', 'LOADER' );

		// ---- Admin (screens/tabs)
		require_once AAA_PAIM_DIR . 'admin/class-aaa-paim-setting.php';
		require_once AAA_PAIM_DIR . 'admin/tabs/aaa-paim-attributeset.php';
		require_once AAA_PAIM_DIR . 'admin/tabs/aaa-paim-global.php';
		require_once AAA_PAIM_DIR . 'admin/tabs/aaa-paim-products.php';

		// ---- Actions/Pages
		require_once AAA_PAIM_DIR . 'admin/post/aaa-paim-start-run.php';
		require_once AAA_PAIM_DIR . 'admin/helpers/aaa-paim-attribute-set-actions.php';
		require_once AAA_PAIM_DIR . 'admin/pages/aaa-paim-run-report.php';

		// ---- AJAX
		require_once AAA_PAIM_DIR . 'ajax/class-aaa-paim-ajax-verify.php';
		require_once AAA_PAIM_DIR . 'ajax/class-aaa-paim-ajax-ai.php';
		
		require_once AAA_PAIM_DIR . 'helpers/aaa-attr-instructions-view.php';


		aaa_paim_log( 'Admin + AJAX includes complete', 'LOADER' );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'AAA Product AI Manager', 'aaa-paim' ),
			__( 'PAIM', 'aaa-paim' ),
			'manage_woocommerce',
			'aaa-paim',
			[ 'AAA_Paim_Setting', 'render' ],
			'dashicons-filter',
			51
		);
		aaa_paim_log( 'Admin menu registered (slug=aaa-paim)', 'LOADER' );
	}
}

// Bootstrap
AAA_Paim_Loader::init();
