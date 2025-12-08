<?php
/**
 * Plugin Name: AAA API Auditor
 * Description: Scans WooCommerce & ATUM REST endpoints across one or more hosts and reports required auth methods.
 * Version: 1.0.1
 * Author: Webmaster Workflow
 * License: GPLv2 or later
 * Text Domain: aaa-api-auditor
 *
 * File: /wp-content/plugins/aaa-api-auditor/aaa-api-auditor.php
 * Purpose: Loader for AAA API Auditor – scans Woo/ATUM endpoints & auth requirements.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Minimum requirements */
if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
	add_action('admin_notices', function(){
		echo '<div class="notice notice-error"><p>AAA API Auditor requires PHP 7.2+.</p></div>';
	});
	return;
}

define( 'AAA_API_AUDITOR_VER', '1.0.1' );
define( 'AAA_API_AUDITOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_API_AUDITOR_URL', plugin_dir_url( __FILE__ ) );

if ( ! function_exists( 'aaa_api_auditor_log' ) ) {
	/** Minimal logger to debug.log */
	function aaa_api_auditor_log( ...$args ) {
		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			error_log( '[AAA-API-AUDITOR] ' . wp_json_encode( $args, JSON_UNESCAPED_SLASHES ) );
		}
	}
}

/** Hard require class files (graceful admin notice if missing) */
$inc_ok = file_exists( AAA_API_AUDITOR_DIR . 'inc/class-aaa-api-auditor-scanner.php' )
	&& file_exists( AAA_API_AUDITOR_DIR . 'admin/class-aaa-api-auditor-admin.php' );

if ( ! $inc_ok ) {
	add_action('admin_notices', function(){
		echo '<div class="notice notice-error"><p>AAA API Auditor: missing required files in /inc or /admin. Please re-copy the plugin.</p></div>';
	});
	return;
}

require_once AAA_API_AUDITOR_DIR . 'inc/class-aaa-api-auditor-scanner.php';
require_once AAA_API_AUDITOR_DIR . 'admin/class-aaa-api-auditor-admin.php';
require_once AAA_API_AUDITOR_DIR . 'admin/class-aaa-api-auditor-report.php';

add_action( 'plugins_loaded', function() {
	if ( class_exists( 'AAA_API_Auditor_Admin' ) ) {
		AAA_API_Auditor_Admin::init();
		AAA_API_Auditor_Report::init();
	} else {
		add_action('admin_notices', function(){
			echo '<div class="notice notice-error"><p>AAA API Auditor failed to initialize. Class not found.</p></div>';
		});
	}
});

/** Activation: create defaults */
register_activation_hook( __FILE__, function() {
	$defaults = array(
		'hosts'   => home_url(),
		'ck'      => '',
		'cs'      => '',
		'jwt'     => '',
		'timeout' => 12,
	);
	if ( ! get_option( 'aaa_api_auditor_opts' ) ) {
		add_option( 'aaa_api_auditor_opts', $defaults, '', false );
	}
});
