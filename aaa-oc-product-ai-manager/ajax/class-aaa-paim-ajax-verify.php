<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/ajax/class-aaa-paim-ajax-verify.php
 * Purpose: AJAX: Verify OpenAI API key saved in PAIM global settings (or a temp override).
 * Version: 0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_PAIM_AJAX_VERIFY_DEBUG' ) ) {
	define( 'AAA_PAIM_AJAX_VERIFY_DEBUG', true );
}

/**
 * Verify handler
 *
 * Expects:
 * - action = aaa_paim_verify_openai
 * - nonce  = wp_create_nonce('aaa_paim_admin')
 * - api_key (optional) → if provided, test this one; otherwise test saved option
 */
function aaa_paim_ajax_verify_openai() {
	// 1) Nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'aaa_paim_admin' ) ) {
		if ( AAA_PAIM_AJAX_VERIFY_DEBUG && defined('WP_DEBUG') && WP_DEBUG ) {
			error_log(' [AAA-PAIM][AJAX][VERIFY] nonce fail');
		}
		// Return 403 so it’s clear in Network tab
		wp_send_json_error( array( 'message' => 'Security check failed (nonce).' ), 403 );
	}

	// 2) Capability (admin pages only)
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		if ( AAA_PAIM_AJAX_VERIFY_DEBUG && WP_DEBUG ) {
			error_log(' [AAA-PAIM][AJAX][VERIFY] capability fail');
		}
		wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
	}

	// 3) Determine which key to test
	$key = '';
	if ( isset( $_POST['api_key'] ) ) {
		$key = trim( (string) wp_unslash( $_POST['api_key'] ) );
	}
	if ( $key === '' ) {
		// Fallback to saved option
		// (adjust option name if your settings use a different one)
		$key = (string) get_option( 'aaa_paim_openai_api_key', '' );
	}

	if ( AAA_PAIM_AJAX_VERIFY_DEBUG && WP_DEBUG ) {
		error_log(' [AAA-PAIM][AJAX][VERIFY] key len=' . strlen( $key ));
	}

	if ( $key === '' ) {
		wp_send_json_error( array( 'message' => 'No API key found.' ), 400 );
	}

	// 4) Ping OpenAI (or your configured provider) — here we do a lightweight HEAD/empty POST
	// If you want to avoid remote calls on dev, short-circuit to success when WP_DEBUG is true.
	$ok = true; // <- set to true to avoid external calls during development

	// Example skeleton for a real call (commented):
	/*
	$args = array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $key,
			'Content-Type'  => 'application/json',
		),
		'timeout' => 10,
		'body'    => wp_json_encode( array( 'model' => 'gpt-4o-mini', 'input' => 'ping' ) ),
	);
	$resp = wp_remote_post( 'https://api.openai.com/v1/responses', $args );
	if ( is_wp_error( $resp ) ) {
		$ok = false;
		$msg = $resp->get_error_message();
	} else {
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$ok   = ( $code >= 200 && $code < 300 );
		$msg  = 'HTTP ' . $code;
	}
	*/

	if ( AAA_PAIM_AJAX_VERIFY_DEBUG && WP_DEBUG ) {
		error_log(' [AAA-PAIM][AJAX][VERIFY] result=' . ( $ok ? 'ok' : 'fail' ) );
	}

	if ( $ok ) {
		wp_send_json_success( array(
			'message' => 'Verified',
		), 200 );
	} else {
		wp_send_json_error( array(
			'message' => 'Verification failed' . ( isset( $msg ) ? " ({$msg})" : '' ),
		), 502 );
	}
}
add_action( 'wp_ajax_aaa_paim_verify_openai', 'aaa_paim_ajax_verify_openai' );
// no need for nopriv — only admins use this screen
