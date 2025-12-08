<?php
/**
 * File: /admin/aaa-afci-export-page.php
 * Purpose: Export full or filtered AFCI logs (sessions + events + details) as downloadable JSON.
 * Version: 1.4.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add submenu under "Checkout Sessions" → "Export Logs"
 */
add_action( 'admin_menu', function() {
	add_submenu_page(
		'aaa-afci-settings',
		'Export Logs',
		'Export Logs',
		'manage_woocommerce',
		'aaa-afci-export',
		'aaa_afci_export_page_render'
	);
});

/**
 * Render export interface + handle exports.
 */
function aaa_afci_export_page_render() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Permission denied.' );
	}
	?>
	<div class="wrap afci-wrap">
		<h1>Export Checkout Logs</h1>
		<p>This tool lets you download AFCI session data as JSON, filtered by Session Key or User ID for easier troubleshooting.</p>

		<style>
			.afci-wrap input[type=text],
			.afci-wrap input[type=number] { width: 320px; }
			.afci-wrap .button-primary { font-size:14px; padding:6px 14px; }
		</style>

		<form method="post" style="margin-top:1em;">
			<h2>🎯 Targeted Export</h2>
			<p>Enter a specific <strong>Session Key</strong> or <strong>User ID</strong> to limit results.</p>
			<table class="form-table">
				<tr>
					<th scope="row">Session Key</th>
					<td><input type="text" name="session_key" placeholder="Exact session key (optional)"></td>
				</tr>
				<tr>
					<th scope="row">User ID</th>
					<td><input type="number" name="user_id" placeholder="Numeric user ID (optional)" min="1"></td>
				</tr>
			</table>
			<?php wp_nonce_field( 'afci_export_filtered' ); ?>
			<p>
				<button type="submit" name="afci_export_filtered" class="button button-primary">📥 Export Filtered Logs</button>
				&nbsp;
				<button type="submit" name="afci_export_all" class="button">📦 Export All Logs</button>
			</p>
		</form>
	</div>
	<?php

	global $wpdb;
	$table_s = $wpdb->prefix . 'aaa_checkout_sessions';
	$table_d = $wpdb->prefix . 'aaa_checkout_event_details';

	/* ----------------------------------------------------------------------
	 * FULL EXPORT
	 * ---------------------------------------------------------------------- */
	if ( isset( $_POST['afci_export_all'] ) ) {
		check_admin_referer( 'afci_export_filtered' );
		$sessions = $wpdb->get_results( "SELECT * FROM {$table_s} ORDER BY id ASC" );
		$details  = $wpdb->get_results( "SELECT * FROM {$table_d} ORDER BY id ASC" );
		aaa_afci_output_json_export( $sessions, $details, [ 'mode' => 'all' ] );
	}

	/* ----------------------------------------------------------------------
	 * FILTERED EXPORT
	 * ---------------------------------------------------------------------- */
	if ( isset( $_POST['afci_export_filtered'] ) ) {
		check_admin_referer( 'afci_export_filtered' );

		$where = [];
		if ( ! empty( $_POST['session_key'] ) ) {
			$where[] = $wpdb->prepare( "session_key = %s", sanitize_text_field( $_POST['session_key'] ) );
		}
		if ( ! empty( $_POST['user_id'] ) ) {
			$where[] = $wpdb->prepare( "user_id = %d", absint( $_POST['user_id'] ) );
		}
		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$sessions = $wpdb->get_results( "SELECT * FROM {$table_s} {$where_sql} ORDER BY id ASC" );

		$details = [];
		if ( $sessions ) {
			$ids = wp_list_pluck( $sessions, 'id' );
			$id_in = implode( ',', array_map( 'absint', $ids ) );
			$details = $wpdb->get_results( "SELECT * FROM {$table_d} WHERE event_id IN ($id_in) ORDER BY id ASC" );
		}

		aaa_afci_output_json_export( $sessions, $details, [
			'mode'        => 'filtered',
			'session_key' => sanitize_text_field( $_POST['session_key'] ?? '' ),
			'user_id'     => absint( $_POST['user_id'] ?? 0 ),
		] );
	}
}

/**
 * Output JSON download response.
 */
function aaa_afci_output_json_export( $sessions, $details, $meta ) {
	$payload = [
		'meta' => [
			'exported_at'    => current_time( 'mysql' ),
			'mode'           => $meta['mode'] ?? 'unknown',
			'session_key'    => $meta['session_key'] ?? '',
			'user_id'        => $meta['user_id'] ?? '',
			'site'           => get_bloginfo( 'name' ),
			'version'        => defined( 'AAA_FCI_VERSION' ) ? AAA_FCI_VERSION : 'unknown',
			'total_sessions' => count( $sessions ),
			'total_details'  => count( $details ),
		],
		'sessions' => $sessions,
		'details'  => $details,
	];

	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	$filename = 'afci-export-' . date( 'Ymd-His' ) . '.json';

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Length: ' . strlen( $json ) );
	}
	echo $json; // phpcs:ignore
	exit;
}
