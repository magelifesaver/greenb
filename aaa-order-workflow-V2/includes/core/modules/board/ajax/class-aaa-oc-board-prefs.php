<?php
/**
 * File Path: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/ajax/class-aaa-oc-board-prefs.php
 * Purpose: Per-user Workflow Board preferences (stored in user meta).
 *          Supported (core-only):
 *            - aaa_oc_hide_completed   ('1' or '0')
 *            - aaa_oc_hide_scheduled   ('1' or '0')
 * Notes:
 *  - The former "collapse empty columns" feature has been removed.
 *  - Accepts either 'aaa_oc_nonce' or 'aaa_oc_ajax_nonce' for AJAX requests.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Per-file debug toggle (safe to leave true in development)
if ( ! defined( 'AAA_OC_BOARD_PREFS_DEBUG' ) ) {
	define( 'AAA_OC_BOARD_PREFS_DEBUG', false );
}

class AAA_OC_Board_Prefs {

	/**
	 * Wire AJAX endpoints
	 */
	public static function init() : void {
		add_action( 'wp_ajax_aaa_oc_get_board_prefs', array( __CLASS__, 'ajax_get' ) );
		add_action( 'wp_ajax_aaa_oc_set_board_pref',  array( __CLASS__, 'ajax_set' ) );
	}

	/**
	 * GET current user's prefs
	 *
	 * Success payload:
	 *   {
	 *     hideCompleted: 0|1,
	 *     hideScheduled: 0|1
	 *   }
	 */
	public static function ajax_get() : void {
		self::verify_nonce_or_die();

		$uid = get_current_user_id();
		if ( ! $uid ) {
			wp_send_json_error( array( 'message' => 'not_logged_in' ), 403 );
		}

		$hide_completed = get_user_meta( $uid, 'aaa_oc_hide_completed', true );
		$hide_scheduled = get_user_meta( $uid, 'aaa_oc_hide_scheduled', true );

		$payload = array(
			'hideCompleted' => ( intval( $hide_completed ) === 1 ) ? 1 : 0,
			'hideScheduled' => ( intval( $hide_scheduled ) === 1 ) ? 1 : 0,
		);

		if ( AAA_OC_BOARD_PREFS_DEBUG ) {
			error_log( '[AAA-OC][BoardPrefs][GET] uid=' . $uid . ' => ' . wp_json_encode( $payload ) );
		}

		wp_send_json_success( $payload );
	}

	/**
	 * SET a single pref
	 * POST expects:
	 *   key   = 'completed' | 'scheduled'
	 *   value = 0 | 1
	 */
	public static function ajax_set() : void {
		self::verify_nonce_or_die();

		$uid = get_current_user_id();
		if ( ! $uid ) {
			wp_send_json_error( array( 'message' => 'not_logged_in' ), 403 );
		}

		$key   = isset( $_POST['key'] )   ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$value = isset( $_POST['value'] ) ? intval( $_POST['value'] ) : 0;

		// Allowlist of keys → user_meta mapping (collapse_empty removed)
		$map = array(
			'completed' => 'aaa_oc_hide_completed',
			'scheduled' => 'aaa_oc_hide_scheduled',
		);

		if ( ! isset( $map[ $key ] ) ) {
			wp_send_json_error( array( 'message' => 'invalid_key' ), 400 );
		}

		update_user_meta( $uid, $map[ $key ], $value ? '1' : '0' );

		if ( AAA_OC_BOARD_PREFS_DEBUG ) {
			error_log( sprintf( '[AAA-OC][BoardPrefs][SET] uid=%d key=%s val=%d', $uid, $key, $value ? 1 : 0 ) );
		}

		wp_send_json_success( array(
			'key'   => $key,
			'value' => $value ? 1 : 0,
		) );
	}

	/**
	 * Accepts either 'aaa_oc_nonce' or 'aaa_oc_ajax_nonce' to support
	 * old/new callers consistently.
	 */
	private static function verify_nonce_or_die() : void {
		$nonce = $_REQUEST['nonce']        ?? null;
		$nonce = $nonce ?: ( $_REQUEST['_ajax_nonce'] ?? null );

		// Prefer the board-wide AJAX nonce label.
		if ( $nonce && wp_verify_nonce( $nonce, 'aaa_oc_ajax_nonce' ) ) {
			return;
		}
		// Back-compat: some callers used this label previously.
		if ( $nonce && wp_verify_nonce( $nonce, 'aaa_oc_nonce' ) ) {
			return;
		}

		wp_send_json_error( array( 'message' => 'bad_nonce' ), 403 );
	}
}

AAA_OC_Board_Prefs::init();
