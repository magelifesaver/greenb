<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/ajax/class-aaa-oc-customer-inline-save.php
 * Purpose: AJAX handler to save Customer Special Needs + Warnings (options + note) from the board, then reindex.
 * Version: 1.1.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'AAA_OC_Customer_Inline_Save' ) ) return;

final class AAA_OC_Customer_Inline_Save {

	const DEBUG_THIS_FILE = true;

	// Meta keys (must match profile editor)
	const META_NEEDS       = '_aaa_oc_special_needs';
	const META_WARN_REASON = '_aaa_oc_warning_reason';
	const META_WARN_BAN    = '_aaa_oc_warning_is_ban';
	const META_BAN_UNTIL   = '_aaa_oc_warning_ban_until';

	public static function init() : void {
		add_action( 'wp_ajax_aaa_oc_save_customer_flags',     [ __CLASS__, 'save' ] );
		add_action( 'wp_ajax_nopriv_aaa_oc_save_customer_flags', [ __CLASS__, 'save' ] ); // belt & suspenders
		if ( self::DEBUG_THIS_FILE ) {
			@error_log('[CUSTOMER][AJAX] inline-save init');
		}
	}

	public static function save() : void {
		// Always JSON
		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_bloginfo( 'charset' ), true );

		// Early trace (no payload dump to avoid PII)
		if ( self::DEBUG_THIS_FILE ) {
			@error_log('[CUSTOMER][AJAX] hit: action=' . ( isset($_POST['action']) ? sanitize_text_field((string)$_POST['action']) : '∅' ) );
		}

		// Require a logged-in capability; if not, still return JSON 200 (avoid browser 400)
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			echo wp_json_encode( [ 'success' => false, 'data' => [ 'msg' => 'cap' ] ] ); exit;
		}

		$nonce = isset($_POST['nonce']) ? (string) $_POST['nonce'] : '';
		if ( ! wp_verify_nonce( $nonce, 'aaa_oc_customer_inline' ) ) {
			if ( self::DEBUG_THIS_FILE ) { @error_log('[CUSTOMER][AJAX] nonce FAIL'); }
			echo wp_json_encode( [ 'success' => false, 'data' => [ 'msg' => 'nonce' ] ] ); exit;
		}

		$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$order_id= isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
		if ( $user_id <= 0 ) {
			echo wp_json_encode( [ 'success' => false, 'data' => [ 'msg' => 'user' ] ] ); exit;
		}

		// Inputs
		$needs_in  = isset($_POST['needs']) ? (array) $_POST['needs'] : [];
		$warn_opts = isset($_POST['warn_opts']) ? (array) $_POST['warn_opts'] : [];
		$warn_note = isset($_POST['warn_note']) ? (string) $_POST['warn_note'] : '';
		$isban     = ( isset($_POST['warn_is_ban']) && $_POST['warn_is_ban'] === 'yes' ) ? 'yes' : 'no';
		$len       = isset($_POST['ban_length']) ? (string) $_POST['ban_length'] : 'none';

		// Sanitize
		$needs = [];
		foreach ( $needs_in as $label ) {
			$label = sanitize_text_field( (string) $label );
			if ( $label !== '' ) $needs[] = $label;
		}
		$warn_labels = [];
		foreach ( $warn_opts as $w ) {
			$w = sanitize_text_field( (string) $w );
			if ( $w !== '' ) $warn_labels[] = $w;
		}
		$warn_note = sanitize_text_field( $warn_note );

		// Compose warning text: selected labels + optional note
		$warn_text = '';
		if ( $warn_labels ) { $warn_text = implode( '; ', $warn_labels ); }
		if ( $warn_note !== '' ) { $warn_text = $warn_text ? ($warn_text . ' — ' . $warn_note) : $warn_note; }
		if ( $isban === 'yes' ) {
			$warn_text = trim( $warn_text . ( $warn_text ? ' ' : '' ) . '(Banned)' );
		}

		// Persist user meta
		update_user_meta( $user_id, self::META_NEEDS, $needs );
		update_user_meta( $user_id, self::META_WARN_REASON, $warn_text );
		update_user_meta( $user_id, self::META_WARN_BAN, $isban );

		$until = 0;
		if ( $isban === 'yes' ) {
			switch ( $len ) {
				case '1_week':   $until = time() + WEEK_IN_SECONDS; break;
				case '1_month':  $until = time() + 30 * DAY_IN_SECONDS; break;
				case '3_months': $until = time() + 90 * DAY_IN_SECONDS; break;
			}
		}
		update_user_meta( $user_id, self::META_BAN_UNTIL, (int) $until );

		// Reindex this order if provided
		if ( $order_id > 0 ) {
			do_action( 'aaa_oc_reindex_customer', $order_id );
		}

		// Build display strings for immediate UI update
		$needs_text    = implode( ', ', $needs );
		$display_warn  = $warn_text;
		if ( $isban === 'yes' && $until > time() ) {
			$display_warn = preg_replace( '/\s*\(Banned\)\s*$/', '', $display_warn );
			$display_warn = trim( $display_warn . ( $display_warn ? ' ' : '' ) . '(Banned until ' . date_i18n( get_option('date_format'), $until ) . ')' );
		}

		if ( self::DEBUG_THIS_FILE ) {
			@error_log('[CUSTOMER][AJAX] saved uid=' . sanitize_text_field((string)$user_id) . ' order=' . sanitize_text_field((string)$order_id) . ' needs=' . count($needs) . ' warnOpts=' . count($warn_labels) . ' note=' . ($warn_note!==''?'1':'0') . ' ban=' . $isban);
		}

		echo wp_json_encode([
			'success' => true,
			'data'    => [
				'warnings_html' => $display_warn !== '' ? esc_html( $display_warn ) : '<em>No warnings yet</em>',
				'needs_html'    => $needs_text   !== '' ? esc_html( $needs_text )   : '<em>No special needs set</em>',
			]
		]); exit;
	}
}

AAA_OC_Customer_Inline_Save::init();
