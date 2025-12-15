<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/hooks/class-aaa-oc-payconfirm-admin-actions.php
 * Purpose: Adds a "Process Now" row action to the Payment Confirmation list table.
 * Notes: Triggers the same processor used by automated flows via the 'aaa_oc_pc_process_post' action.
 * Version: AAA_OC_VERSION
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_PayConfirm_Admin_Actions' ) ) :

class AAA_OC_PayConfirm_Admin_Actions {

	/** Per-file debug toggle (set to false to silence just this file) */
	const DEBUG_THIS_FILE = false;

	/** Bootstrap hooks. */
	public static function init() {
		add_filter( 'post_row_actions', [ __CLASS__, 'row_action' ], 10, 2 );
		add_action( 'admin_post_aaa_oc_pc_process_now', [ __CLASS__, 'process_now' ] );
		self::log( 'Admin actions initialized' );
	}

	/**
	 * Add "Process Now" link to each payment-confirmation row.
	 *
	 * @param array   $actions
	 * @param WP_Post $post
	 * @return array
	 */
	public static function row_action( $actions, $post ) {
		if ( ! ( $post instanceof WP_Post ) || $post->post_type !== 'payment-confirmation' ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=aaa_oc_pc_process_now&post_id=' . (int) $post->ID ),
			'aaa_oc_pc_process_now_' . (int) $post->ID
		);

		$actions['aaa_oc_pc_process_now'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Process Now', 'aaa-oc' ) . '</a>';
		self::log( 'Row action added for post ' . (int) $post->ID );

		return $actions;
	}

	/**
	 * Handle the "Process Now" action.
	 * Verifies nonce and capability, then fires the processor hook.
	 */
	public static function process_now() {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		self::log( 'Process Now clicked for post ' . $post_id );

		// Basic validation.
		if ( ! $post_id ) {
			self::log( 'Abort: missing post_id' );
			wp_die( esc_html__( 'Invalid request (missing post ID).', 'aaa-oc' ) );
		}
		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_oc_pc_process_now_' . $post_id ) ) {
			self::log( 'Abort: bad nonce for post ' . $post_id );
			wp_die( esc_html__( 'Invalid request (bad nonce).', 'aaa-oc' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'payment-confirmation' ) {
			self::log( 'Abort: wrong post type for post ' . $post_id );
			wp_die( esc_html__( 'Invalid request (wrong post type).', 'aaa-oc' ) );
		}

		// Capability: allow editors/admins or post editors to run it.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			self::log( 'Abort: insufficient capability for post ' . $post_id );
			wp_die( esc_html__( 'You do not have permission to process this item.', 'aaa-oc' ) );
		}

		// Kick the shared processor (immediate path used by our triggers).
		do_action( 'aaa_oc_pc_process_post', $post_id );
		self::log( 'Processor action fired for post ' . $post_id );

		// Return to the list table (or referrer).
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'edit.php?post_type=payment-confirmation' ) );
		exit;
	}

	/** Internal logger: respects per-file toggle AND global AAA_OC_PAYCONFIRM_DEBUG (if defined) */
	protected static function log( $msg ) {
		$global = defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) ? (bool) AAA_OC_PAYCONFIRM_DEBUG : true;
		if ( self::DEBUG_THIS_FILE && $global ) {
			error_log( '[AAA-OC][PayConfirm][ADMIN] ' . $msg );
		}
	}
}

endif; // class exists

// Boot.
AAA_OC_PayConfirm_Admin_Actions::init();
