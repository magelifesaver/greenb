<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/includes/api/lokey-client.php
 * Description: Internal LokeyReports client with recursion-safe header.
 * Version: 1.3.1
 * Updated: 2025-12-02
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get WooCommerce sales summary from LokeyReports safely.
 *
 * @param string $from Start date (YYYY-MM-DD)
 * @param string $to   End date (YYYY-MM-DD)
 * @return array
 */
function aaa_wf_ai_get_sales_summary( $from, $to ) {
	$url = rest_url( sprintf( 'lokeyreports/v1/sales/summary?from=%s&to=%s', $from, $to ) );

	// 👇 add recursion-safe header so MU plugin won't autoload again
	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'X-Lokey-Internal' => '1',
		],
	] );

	if ( is_wp_error( $response ) ) {
		aaa_wf_ai_debug('Lokey API error: ' . $response->get_error_message(), basename(__FILE__), 'lokey-client');
		return [];
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( $code !== 200 ) {
		aaa_wf_ai_debug("LokeyReports HTTP {$code} for {$from}→{$to}", basename(__FILE__), 'lokey-client');
		return [];
	}

	if ( empty( $data ) || ! isset( $data['totals'] ) ) {
		aaa_wf_ai_debug("LokeyReports empty response {$from}→{$to}", basename(__FILE__), 'lokey-client');
		return [];
	}

	aaa_wf_ai_debug("✅ LokeyReports summary retrieved {$from}→{$to}", basename(__FILE__), 'lokey-client');
	return $data;
}
