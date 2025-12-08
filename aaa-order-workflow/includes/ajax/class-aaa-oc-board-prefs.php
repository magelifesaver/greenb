<?php
/**
 * File Path: /wp-content/plugins/aaa-order-workflow/includes/ajax/class-aaa-oc-board-prefs.php
 * Purpose: Per-user board preferences (hide/show columns) stored in user meta.
 * Meta keys:
 *   - aaa_oc_hide_completed   ('1' or '0')
 *   - aaa_oc_hide_scheduled   ('1' or '0')
 *   - aaa_oc_collapse_empty   ('1' or '0')  // NEW: "Collapse/Hide if Empty" board columns
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AAA_OC_BOARD_PREFS_DEBUG' ) ) {
	define( 'AAA_OC_BOARD_PREFS_DEBUG', false );
}

class AAA_OC_Board_Prefs {

	public static function init() {
		add_action( 'wp_ajax_aaa_oc_get_board_prefs', array( __CLASS__, 'ajax_get' ) );
		add_action( 'wp_ajax_aaa_oc_set_board_pref',  array( __CLASS__, 'ajax_set' ) );
	}

	/**
	 * Return current user's prefs.
	 * Success payload:
	 *  {
	 *    hideCompleted: 0|1,
	 *    hideScheduled: 0|1,
	 *    collapseEmpty: 0|1
	 *  }
	 */
	public static function ajax_get() {
		check_ajax_referer( 'aaa_oc_nonce' );
		$uid = get_current_user_id();
		if ( ! $uid ) {
			wp_send_json_error( array( 'message' => 'Not logged in' ), 403 );
		}

		$hide_completed = get_user_meta( $uid, 'aaa_oc_hide_completed', true );
		$hide_scheduled = get_user_meta( $uid, 'aaa_oc_hide_scheduled', true );
		$collapse_empty = get_user_meta( $uid, 'aaa_oc_collapse_empty', true ); // NEW

		$payload = array(
			'hideCompleted' => ( intval( $hide_completed ) === 1 ) ? 1 : 0,
			'hideScheduled' => ( intval( $hide_scheduled ) === 1 ) ? 1 : 0,
			'collapseEmpty' => ( intval( $collapse_empty ) === 1 ) ? 1 : 0, // NEW
		);

		if ( AAA_OC_BOARD_PREFS_DEBUG ) {
			error_log( '[AAA-OC][BoardPrefs] GET uid=' . $uid . ' payload=' . wp_json_encode( $payload ) );
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Save one pref:
	 *   POST: key=completed|scheduled|collapse_empty, value=0|1
	 */
	public static function ajax_set() {
		check_ajax_referer( 'aaa_oc_nonce' );
		$uid = get_current_user_id();
		if ( ! $uid ) {
			wp_send_json_error( array( 'message' => 'Not logged in' ), 403 );
		}

		$key   = isset( $_POST['key'] )   ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$value = isset( $_POST['value'] ) ? intval( $_POST['value'] ) : 0;

		// Whitelist of allowed keys → user_meta mapping.
		$map = array(
			'completed'      => 'aaa_oc_hide_completed',
			'scheduled'      => 'aaa_oc_hide_scheduled',
			'collapse_empty' => 'aaa_oc_collapse_empty', // NEW
		);

		if ( ! isset( $map[ $key ] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid key' ), 400 );
		}

		update_user_meta( $uid, $map[ $key ], $value ? '1' : '0' );

		if ( AAA_OC_BOARD_PREFS_DEBUG ) {
			error_log( sprintf( '[AAA-OC][BoardPrefs] SET uid=%d key=%s value=%d', $uid, $key, $value ? 1 : 0 ) );
		}

		wp_send_json_success( array(
			'key'   => $key,
			'value' => $value ? 1 : 0,
		) );
	}
}

AAA_OC_Board_Prefs::init();
