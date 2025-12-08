<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/aaa-oc-payconfirm-assets-loader.php
 * Purpose: Enqueue ONLY the PayConfirm sidebar feed JS on the Workflow Board screen.
 * Version: 1.4.7
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) ) define( 'AAA_OC_PAYCONFIRM_DEBUG', true );

class AAA_OC_PayConfirm_Assets {
	public static function init() {
		if ( is_admin() ) add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	public static function enqueue( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( $page !== 'aaa-oc-workflow-board' ) return;

		$rel_path  = 'assets/js/board-sidebar-feed.js';
		$fs_path   = trailingslashit( dirname( __FILE__ ) ) . $rel_path;
		$script_url= plugins_url( $rel_path, __FILE__ );
		$ver       = file_exists( $fs_path ) ? (string) filemtime( $fs_path ) : ( defined('AAA_OC_PAYCONFIRM_VERSION') ? AAA_OC_PAYCONFIRM_VERSION : '1.0.0' );

		// Depend only on jQuery (don’t force toolbar handles that may version-drift)
		wp_enqueue_script(
			'aaa-oc-board-sidebar-feed',
			$script_url,
			[ 'jquery' ],
			$ver,
			true
		);

		wp_localize_script(
			'aaa-oc-board-sidebar-feed',
			'AAA_OC_PCFeed',
			[
				'endpoint'   => rest_url( 'aaa-oc/v1/payconfirm' ),
				'per_page'   => 20,
				'asset_ver'  => $ver,
				'plugin_ver' => ( defined('AAA_OC_VERSION') ? AAA_OC_VERSION : 'n/a' ),
				'pc_ver'     => ( defined('AAA_OC_PAYCONFIRM_VERSION') ? AAA_OC_PAYCONFIRM_VERSION : 'n/a' ),
			]
		);

		if ( AAA_OC_PAYCONFIRM_DEBUG ) {
			wp_add_inline_script(
				'aaa-oc-board-sidebar-feed',
				"try{console.log('[PayConfirm][Assets] feed.js v=' + (AAA_OC_PCFeed && AAA_OC_PCFeed.asset_ver) + ', core=' + (AAA_OC_PCFeed && AAA_OC_PCFeed.plugin_ver) + ', pc=' + (AAA_OC_PCFeed && AAA_OC_PCFeed.pc_ver));}catch(e){}",
				'after'
			);
			error_log( '[PayConfirm][Assets] Enqueued feed.js v' . $ver );
		}
	}
}
AAA_OC_PayConfirm_Assets::init();
