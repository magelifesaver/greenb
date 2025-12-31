<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/aaa-oc-payconfirm-assets-loader.php
 * Purpose: Enqueue the PayConfirm sidebar feed on the Workflow Board page.
 *          - Always enqueue the feed JS
 *          - Localize REST endpoint if present, else leave blank (JS will fallback to admin-ajax)
 * Version: 1.2.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_PayConfirm_Assets' ) ) {
final class AAA_OC_PayConfirm_Assets {

	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
		}
	}

	public static function enqueue() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( $page !== 'aaa-oc-workflow-board' ) return;

		$base_dir = trailingslashit( AAA_OC_PLUGIN_DIR . 'includes/payconfirm/assets/js' );
		$base_url = trailingslashit( AAA_OC_PLUGIN_URL . 'includes/payconfirm/assets/js' );

		$ver = function( $rel ) use ( $base_dir ) {
			$path = $base_dir . ltrim( $rel, '/' );
			return file_exists( $path )
				? (string) filemtime( $path )
				: ( defined('AAA_OC_VERSION') ? AAA_OC_VERSION : '1.0.0' );
		};

		// --- Work out REST endpoint if the route is registered ---
		$endpoint = '';
		if ( function_exists( 'rest_get_server' ) ) {
			$server = rest_get_server();
			if ( $server ) {
				$routes = $server->get_routes();
				// Route key must include namespace; this is how WP stores it internally.
				if ( isset( $routes['/aaa-oc/v1/payconfirm'] ) ) {
					$endpoint = rest_url( 'aaa-oc/v1/payconfirm' );
				}
			}
		}

		// Localize feed config (JS will fallback to admin-ajax if endpoint is empty)
		wp_register_script(
			'aaa-oc-payconfirm-feed',
			$base_url . 'board-sidebar-feed.js',
			[ 'jquery' ],
			$ver( 'board-sidebar-feed.js' ),
			true
		);

		wp_localize_script( 'aaa-oc-payconfirm-feed', 'AAA_OC_PCFeed', [
			'endpoint'   => $endpoint,                         // '' means "no REST" → JS will use admin-ajax
			'rest_nonce' => function_exists('wp_create_nonce') ? wp_create_nonce( 'wp_rest' ) : '',
			'per_page'   => 20,
		] );

		// Also expose ajax URL + nonce + admin edit base to the feed script
		wp_localize_script( 'aaa-oc-payconfirm-feed', 'AAA_OC_Vars', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => function_exists('wp_create_nonce') ? wp_create_nonce( 'aaa_oc_ajax_nonce' ) : '',
			'adminEditBase' => admin_url( 'post.php?action=edit&post=' ),
		] );

		wp_enqueue_script( 'aaa-oc-payconfirm-feed' );

		if ( defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log') ) {
			error_log( sprintf(
				'[PayConfirm][Assets] feed enqueued; endpoint=%s',
				$endpoint ? $endpoint : '(none, will use admin-ajax)'
			) );
		}
	}
}}
AAA_OC_PayConfirm_Assets::init();
