<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/includes/api/openai-client.php
 * Description: Handles OpenAI API communication for AI-driven WooCommerce
 *              report summaries, including configurable model + prompt.
 * Version: 2.0.0
 * Updated: 2025-12-02
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * 🧠 Analyze WooCommerce Sales Summary using OpenAI
 * --------------------------------------------------------------------------
 *
 * @param array $data Sales summary data (decoded JSON).
 * @return string Human-readable AI summary.
 */
function aaa_wf_ai_analyze_sales( $data ) {

	// --- Retrieve key + model ---
	$key   = aaa_wf_ai_get_option( 'aaa_wf_ai_openai_key', '' );
	$model = aaa_wf_ai_get_option( 'aaa_wf_ai_default_model', 'gpt-4o-mini' );

	if ( empty( $key ) ) {
		aaa_wf_ai_debug( '❌ OpenAI key missing — cannot analyze.', basename( __FILE__ ), 'openai-client' );
		return '⚠️ OpenAI API key not configured.';
	}

	// --- Prompt template ---
	$prompt_template = aaa_wf_ai_get_option(
		'aaa_wf_ai_prompt_template',
		"You are an AI data analyst. Given this WooCommerce sales summary JSON, produce a concise, clear narrative report including total orders, revenue, average order value, and any trends. "
		. "If totals exist, describe them. If not, state that there were no sales. "
		. "Respond in professional human-readable format.\n\nJSON:\n\n"
	);

	// --- Prepare input data ---
	$input_json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	$prompt_full = $prompt_template . $input_json;

	aaa_wf_ai_debug( 'Prompt (truncated): ' . substr( $prompt_full, 0, 2000 ), basename( __FILE__ ), 'openai-client' );

	// --- Build request body ---
	$body = [
		'model' => $model,
		'messages' => [
			[
				'role'    => 'system',
				'content' => 'You are a business analytics assistant that summarizes e-commerce sales performance for management reports.',
			],
			[
				'role'    => 'user',
				'content' => $prompt_full,
			],
		],
		'temperature' => 0.3,
		'max_tokens'  => 800,
	];

	// --- Send request ---
	$response = wp_remote_post(
		'https://api.openai.com/v1/chat/completions',
		[
			'timeout' => 60,
			'headers' => [
				'Authorization' => "Bearer {$key}",
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( $body ),
		]
	);

	// --- Handle WP errors ---
	if ( is_wp_error( $response ) ) {
		aaa_wf_ai_debug_wp_error( $response, 'openai_request_error', basename( __FILE__ ) );
		return '❌ Network error: ' . esc_html( $response->get_error_message() );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body_raw = wp_remote_retrieve_body( $response );
	$json = json_decode( $body_raw, true );

	if ( $code !== 200 ) {
		$err_msg = $json['error']['message'] ?? "HTTP {$code}";
		aaa_wf_ai_debug( "❌ OpenAI error: {$err_msg}", basename( __FILE__ ), 'openai-client' );
		return "❌ OpenAI error: {$err_msg}";
	}

	// --- Extract AI content ---
	$content = trim( $json['choices'][0]['message']['content'] ?? '' );

	if ( empty( $content ) ) {
		aaa_wf_ai_debug( '⚠️ Empty AI response.', basename( __FILE__ ), 'openai-client' );
		return '⚠️ No response from AI model.';
	}

	// --- Log success ---
	aaa_wf_ai_debug(
		sprintf( '✅ AI summary generated using model "%s" (%d chars).', $model, strlen( $content ) ),
		basename( __FILE__ ),
		'openai-client'
	);

	return $content;
}
