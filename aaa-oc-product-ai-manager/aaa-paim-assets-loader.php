<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/aaa-paim-assets-loader.php
 * Purpose: Central PAIM assets loader (admin-only). Enqueues CSS/JS on PAIM admin screens.
 * Version: 1.0.3
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_PAIM_ASSETS_DEBUG' ) ) {
	define( 'AAA_PAIM_ASSETS_DEBUG', true );
}

function aaa_paim_enqueue_admin_assets( $hook_suffix ) {
	$master_debug = ( class_exists('AAA_Paim_Options') ? (bool) AAA_Paim_Options::get('debug', 1) : true );

	// Always log the hook we see so we can diagnose routing (only if master debug is on).
	if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && defined('WP_DEBUG') && WP_DEBUG ) {
		$screen_id = '';
		if ( function_exists('get_current_screen') ) {
			$screen = get_current_screen();
			$screen_id = $screen ? (string) $screen->id : '';
		}
		error_log( sprintf('[AAA-PAIM][ASSETS] admin_enqueue_scripts fired: hook=%s screen=%s page=%s',
			(string)$hook_suffix,
			$screen_id,
			isset($_GET['page']) ? sanitize_key($_GET['page']) : ''
		) );
	}

	// Robust detection of PAIM pages
	$slug_match  = 'aaa-paim';
	$should_load = ( strpos( (string) $hook_suffix, $slug_match ) !== false );

	if ( ! $should_load && function_exists('get_current_screen') ) {
		$screen = get_current_screen();
		if ( $screen && strpos( (string) $screen->id, $slug_match ) !== false ) {
			$should_load = true;
		}
	}
	if ( ! $should_load && isset($_GET['page']) && $_GET['page'] === 'aaa-paim' ) {
		$should_load = true;
	}

	if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && defined('WP_DEBUG') && WP_DEBUG ) {
		error_log( '[AAA-PAIM][ASSETS] decision: ' . ( $should_load ? 'LOAD' : 'SKIP' ) );
	}

	if ( ! $should_load ) {
		return;
	}

	// Paths
	if ( ! defined( 'AAA_PAIM_DIR' ) ) { define( 'AAA_PAIM_DIR', plugin_dir_path( __FILE__ ) ); }
	if ( ! defined( 'AAA_PAIM_URL' ) ) { define( 'AAA_PAIM_URL', plugin_dir_url( __FILE__ ) ); }

	$css_rel      = 'assets/css/aaa-paim-admin.css';
	$js_admin_rel = 'assets/js/aaa-paim-admin.js';
	$js_ai_rel    = 'assets/js/aaa-paim-ai.js';

	$css_abs      = AAA_PAIM_DIR . $css_rel;
	$js_admin_abs = AAA_PAIM_DIR . $js_admin_rel;
	$js_ai_abs    = AAA_PAIM_DIR . $js_ai_rel;

	$css_url      = AAA_PAIM_URL . $css_rel;
	$js_admin_url = AAA_PAIM_URL . $js_admin_rel;
	$js_ai_url    = AAA_PAIM_URL . $js_ai_rel;

	$ver_fallback = defined('AAA_PAIM_VERSION') ? AAA_PAIM_VERSION : '1';
	$css_ver      = file_exists( $css_abs )       ? (string) filemtime( $css_abs )       : $ver_fallback;
	$js_admin_ver = file_exists( $js_admin_abs )  ? (string) filemtime( $js_admin_abs )  : $ver_fallback;
	$js_ai_ver    = file_exists( $js_ai_abs )     ? (string) filemtime( $js_ai_abs )     : $ver_fallback;

	// Localized data shared by admin + AI scripts (note: debug follows saved option)
	$local = array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'aaa_paim_admin' ),
		'debug'    => $master_debug ? 1 : 0,
	);

	// CSS
	if ( file_exists( $css_abs ) ) {
		wp_enqueue_style( 'aaa-paim-admin', $css_url, array(), $css_ver );
		if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && WP_DEBUG ) {
			error_log( ' [AAA-PAIM][ASSETS] style enqueued: aaa-paim-admin (ver ' . $css_ver . ')' );
		}
	} else {
		if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && WP_DEBUG ) {
			error_log( ' [AAA-PAIM][ASSETS] style missing: ' . $css_rel );
		}
	}

	// Admin JS
	if ( file_exists( $js_admin_abs ) ) {
		wp_enqueue_script( 'aaa-paim-admin', $js_admin_url, array( 'jquery', 'wp-util' ), $js_admin_ver, true );
		wp_localize_script( 'aaa-paim-admin', 'AAA_PAIM', $local );
		if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && WP_DEBUG ) {
			error_log( ' [AAA-PAIM][ASSETS] script enqueued: aaa-paim-admin (ver ' . $js_admin_ver . ')' );
		}
	} else {
		if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && WP_DEBUG ) {
			error_log( ' [AAA-PAIM][ASSETS] script missing: ' . $js_admin_rel );
		}
	}

	// AI JS
	if ( file_exists( $js_ai_abs ) ) {
		wp_enqueue_script( 'aaa-paim-ai', $js_ai_url, array( 'jquery', 'wp-util' ), $js_ai_ver, true );
		wp_localize_script( 'aaa-paim-ai', 'AAA_PAIM', $local ); // ensure always present
		if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && WP_DEBUG ) {
			error_log( ' [AAA-PAIM][ASSETS] script enqueued: aaa-paim-ai (ver ' . $js_ai_ver . ')' );
		}
	} else {
		if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && WP_DEBUG ) {
			error_log( ' [AAA-PAIM][ASSETS] script missing: ' . $js_ai_rel );
		}
	}

	if ( $master_debug && AAA_PAIM_ASSETS_DEBUG && defined('WP_DEBUG') && WP_DEBUG ) {
		error_log( sprintf(
			'[AAA-PAIM][ASSETS] summary css:%s js_admin:%s js_ai:%s',
			file_exists($css_abs)?'yes':'no',
			file_exists($js_admin_abs)?'yes':'no',
			file_exists($js_ai_abs)?'yes':'no'
		) );
	}
}
add_action( 'admin_enqueue_scripts', 'aaa_paim_enqueue_admin_assets', 20 );
