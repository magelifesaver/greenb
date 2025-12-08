<?php
/**
 * File: /admin/aaa-afci-maintenance-page.php
 * Purpose: Admin maintenance page for AFCI — clear log + truncate tables.
 * Version: 1.4.5
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function() {
	add_submenu_page(
		'aaa-afci-settings',
		'AFCI Maintenance',
		'Maintenance',
		'manage_options',
		'aaa-afci-maintenance',
		'aaa_afci_maintenance_render'
	);
});

function aaa_afci_maintenance_render() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	global $wpdb;

	$log_path  = AAA_FCI_PATH . 'logs/aaa-checkout-intervention.log';
	$sessions  = $wpdb->prefix . 'aaa_checkout_sessions';
	$details   = $wpdb->prefix . 'aaa_checkout_event_details';

	// Handle clear actions
	if ( isset( $_POST['afci_action'] ) && check_admin_referer( 'afci_maint_action' ) ) {
		$action = sanitize_text_field( $_POST['afci_action'] );

		switch ( $action ) {
			case 'clear_log':
				if ( file_exists( $log_path ) ) @unlink( $log_path );
				echo '<div class="updated"><p>✅ Log file cleared.</p></div>';
				break;

			case 'clear_db':
				$wpdb->query( "TRUNCATE TABLE {$sessions}" );
				$wpdb->query( "TRUNCATE TABLE {$details}" );
				echo '<div class="updated"><p>✅ All AFCI database tables cleared.</p></div>';
				break;
		}
	}

	// Row counts
	$count_sessions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions}" );
	$count_details  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$details}" );
	$log_exists     = file_exists( $log_path );
	$log_size       = $log_exists ? size_format( filesize( $log_path ), 2 ) : '0 B';
	?>
	<div class="wrap">
		<h1>AFCI Maintenance Tools</h1>
		<p><em>Use carefully — these tools permanently clear logs and session data.</em></p>

		<table class="widefat fixed striped" style="max-width:700px;">
			<thead><tr><th>Resource</th><th>Status</th><th>Action</th></tr></thead>
			<tbody>
				<tr>
					<td><strong>Log File</strong> <code><?php echo esc_html( basename( $log_path ) ); ?></code></td>
					<td><?php echo $log_exists ? "Exists ({$log_size})" : 'Not found'; ?></td>
					<td>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'afci_maint_action' ); ?>
							<input type="hidden" name="afci_action" value="clear_log">
							<button class="button">Clear Log File</button>
						</form>
					</td>
				</tr>
				<tr>
					<td><strong>Session Table</strong> <code><?php echo esc_html( $sessions ); ?></code></td>
					<td><?php echo esc_html( number_format_i18n( $count_sessions ) ); ?> rows</td>
					<td>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'afci_maint_action' ); ?>
							<input type="hidden" name="afci_action" value="clear_db">
							<button class="button button-danger" onclick="return confirm('This will permanently clear all AFCI data. Continue?');">Clear Tables</button>
						</form>
					</td>
				</tr>
				<tr>
					<td><strong>Details Table</strong> <code><?php echo esc_html( $details ); ?></code></td>
					<td><?php echo esc_html( number_format_i18n( $count_details ) ); ?> rows</td>
					<td><em>Included in “Clear Tables” action.</em></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php
}
