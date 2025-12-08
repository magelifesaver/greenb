<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/admin/ajax.php
 * Description: Handles admin actions and AJAX operations for the plugin.
 *              Supports secure nonce handling, OpenAI key verification,
 *              permission saving, and AI-powered report analysis.
 * Version: 2.0.0
 * Updated: 2025-12-02
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * 🔐 Save OpenAI Settings (API Key, Model, Debug)
 * --------------------------------------------------------------------------
 */
add_action( 'admin_post_aaa_wf_ai_save_openai', function() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Access denied' );

	check_admin_referer( 'aaa_wf_ai_save_openai', 'aaa_wf_ai_nonce_field' );

	$key   = sanitize_text_field( $_POST['aaa_wf_ai_openai_key'] ?? '' );
	$debug = ! empty( $_POST['aaa_wf_ai_debug_enabled'] ) ? 1 : 0;
	$model = sanitize_text_field( $_POST['aaa_wf_ai_default_model'] ?? '' );

	aaa_wf_ai_save_option( 'aaa_wf_ai_openai_key', $key );
	aaa_wf_ai_save_option( 'aaa_wf_ai_debug_enabled', $debug );
	if ( $model ) aaa_wf_ai_save_option( 'aaa_wf_ai_default_model', $model );

	aaa_wf_ai_debug( '✅ Saved OpenAI settings (key, debug, model).', basename( __FILE__ ), 'ajax' );

	wp_safe_redirect( admin_url( 'admin.php?page=aaa-workflow-ai-reports&tab=openai&updated=1' ) );
	exit;
});

/**
 * --------------------------------------------------------------------------
 * 🧾 Save User Permissions (Allowed Roles)
 * --------------------------------------------------------------------------
 */
add_action( 'admin_post_aaa_wf_ai_save_permissions', function() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Access denied' );

	check_admin_referer( 'aaa_wf_ai_save_permissions' );

	$roles = array_map( 'sanitize_text_field', (array) ( $_POST['aaa_wf_ai_allowed_roles'] ?? [] ) );
	aaa_wf_ai_save_option( 'aaa_wf_ai_allowed_roles', $roles );

	aaa_wf_ai_debug( '✅ Saved permissions roles list: ' . implode( ',', $roles ), basename( __FILE__ ), 'ajax' );

	wp_safe_redirect( admin_url( 'admin.php?page=aaa-workflow-ai-reports&tab=permissions&updated=1' ) );
	exit;
});

/**
 * --------------------------------------------------------------------------
 * 🧠 AJAX: Verify OpenAI API Key
 * --------------------------------------------------------------------------
 */
add_action( 'wp_ajax_aaa_wf_ai_verify_openai_key', function() {
	check_ajax_referer( 'aaa_wf_ai_nonce', 'nonce' );

	$key = aaa_wf_ai_get_option( 'aaa_wf_ai_openai_key', '', 'global' );

	if ( empty( $key ) ) {
		wp_send_json_error( [ 'message' => '❌ No API key saved. Please enter and save it first.' ] );
	}

	$response = wp_remote_get(
		'https://api.openai.com/v1/models',
		[
			'headers' => [
				'Authorization' => "Bearer {$key}",
				'Content-Type'  => 'application/json',
			],
			'timeout' => 20,
		]
	);

	if ( is_wp_error( $response ) ) {
		$msg = $response->get_error_message();
		aaa_wf_ai_debug( "❌ OpenAI verify network error: {$msg}", basename( __FILE__ ), 'ajax' );
		wp_send_json_error( [ 'message' => 'Network error: ' . esc_html( $msg ) ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code === 200 && isset( $body['data'] ) ) {
		$models = wp_list_pluck( $body['data'], 'id' );
		$models = array_values( array_filter( $models, fn( $m ) => preg_match( '/^(gpt\-4o|gpt\-3\.5)/', $m ) ) );

		aaa_wf_ai_save_option( 'aaa_wf_ai_models_list', $models );
		aaa_wf_ai_save_option( 'aaa_wf_ai_verified_at', current_time( 'mysql' ) );

		// Preserve user-selected model if valid
		$current_model = aaa_wf_ai_get_option( 'aaa_wf_ai_default_model', 'gpt-4o-mini' );
		if ( ! in_array( $current_model, $models, true ) ) {
			aaa_wf_ai_save_option( 'aaa_wf_ai_default_model', $models[0] ?? 'gpt-4o-mini' );
		}

		aaa_wf_ai_debug( '✅ API key verified. Models found: ' . implode( ', ', $models ), basename( __FILE__ ), 'ajax' );
		wp_send_json_success( [
			'message' => 'API key verified successfully (' . count( $models ) . ' models found)',
			'models'  => $models,
		] );
	}

	$error_message = $body['error']['message'] ?? "Unknown error (HTTP {$code})";
	aaa_wf_ai_debug( "❌ Verification failed: {$error_message}", basename( __FILE__ ), 'ajax' );
	wp_send_json_error( [ 'message' => $error_message ] );
});

/**
 * --------------------------------------------------------------------------
 * 📊 AJAX: Run AI Analysis on WooCommerce Sales Summary
 * --------------------------------------------------------------------------
 */
add_action( 'wp_ajax_aaa_wf_ai_run_report', function() {
	check_ajax_referer( 'aaa_wf_ai_nonce', 'nonce' );

	$data_json = stripslashes( $_POST['data'] ?? '' );
	$data      = json_decode( $data_json, true );

	if ( empty( $data ) || empty( $data['totals'] ) ) {
		$from = sanitize_text_field( $_POST['from'] ?? date( 'Y-m-d' ) );
		$to   = sanitize_text_field( $_POST['to'] ?? date( 'Y-m-d' ) );
		aaa_wf_ai_debug( "⚠️ No REST data received — fetching manually for {$from} → {$to}", basename( __FILE__ ), 'ajax' );
		$data = aaa_wf_ai_get_sales_summary( $from, $to );
	}

	if ( empty( $data ) || ! is_array( $data ) || empty( $data['totals'] ) ) {
		aaa_wf_ai_debug( '❌ Invalid or empty sales data — skipping AI analysis.', basename( __FILE__ ), 'ajax' );
		wp_send_json_success( [
			'summary' => '⚠️ No valid data found for analysis. Ensure the endpoint returns valid results.',
			'raw'     => $data,
		] );
		exit;
	}

	// Analyze via OpenAI
	$result = aaa_wf_ai_analyze_sales( $data );

	aaa_wf_ai_debug( '✅ AI report analysis complete.', basename( __FILE__ ), 'ajax' );

	wp_send_json_success( [
		'summary' => $result,
		'raw'     => $data,
	] );
});
