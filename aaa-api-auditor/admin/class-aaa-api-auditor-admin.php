<?php
/**
 * File: /wp-content/plugins/aaa-api-auditor/admin/class-aaa-api-auditor-admin.php
 * Purpose: Admin UI – settings, scan trigger, and report table.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_API_Auditor_Admin {
	const DEBUG_THIS_FILE = true;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_aaa_api_auditor_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_aaa_api_auditor_scan', array( __CLASS__, 'scan' ) );
		add_action( 'admin_enqueue_scripts', function( $hook ){
			if ( $hook === 'tools_page_aaa-api-auditor' ) {
				wp_enqueue_script( 'aaa-api-auditor-admin', AAA_API_AUDITOR_URL.'assets/js/aaa-api-auditor-admin.js', array('jquery'), AAA_API_AUDITOR_VER, true );
			}
		});
	}

	public static function menu() {
		add_management_page(
			'AAA API Auditor',
			'AAA API Auditor',
			'manage_options',
			'aaa-api-auditor',
			array( __CLASS__, 'render' )
		);
	}

	public static function save() {
		if ( ! current_user_can('manage_options') ) { wp_die('Forbidden'); }
		check_admin_referer( 'aaa_api_auditor_save' );
		$opts = get_option( 'aaa_api_auditor_opts', array() );
		$opts['hosts']   = sanitize_text_field( wp_unslash( $_POST['hosts'] ?? '' ) );
		$opts['ck']      = sanitize_text_field( wp_unslash( $_POST['ck'] ?? '' ) );
		$opts['cs']      = sanitize_text_field( wp_unslash( $_POST['cs'] ?? '' ) );
		$opts['jwt']     = sanitize_text_field( wp_unslash( $_POST['jwt'] ?? '' ) );
		$opts['timeout'] = max( 3, intval( $_POST['timeout'] ?? 12 ) );
		update_option( 'aaa_api_auditor_opts', $opts, false );
		wp_redirect( add_query_arg( array( 'page'=>'aaa-api-auditor', 'updated'=>'1' ), admin_url('tools.php') ) );
		exit;
	}

	public static function scan() {
		if ( ! current_user_can('manage_options') ) { wp_die('Forbidden'); }
		check_admin_referer( 'aaa_api_auditor_scan' );
		$opts = get_option( 'aaa_api_auditor_opts', array() );
		$hosts = array_map( 'trim', explode( ',', $opts['hosts'] ?? '' ) );
		$report = AAA_API_Auditor_Scanner::scan_hosts( $hosts, $opts['ck'] ?? '', $opts['cs'] ?? '', $opts['jwt'] ?? '' );
		set_transient( 'aaa_api_auditor_last_report', $report, 10 * MINUTE_IN_SECONDS );
		wp_redirect( add_query_arg( array( 'page'=>'aaa-api-auditor', 'scanned'=>'1' ), admin_url('tools.php') ) );
		exit;
	}

	public static function render() {
		if ( ! current_user_can('manage_options') ) { return; }
		$opts = get_option( 'aaa_api_auditor_opts', array() );
		$report = get_transient( 'aaa_api_auditor_last_report' );
		?>
		<div class="wrap">
			<h1>AAA API Auditor</h1>
			<p>Enter one or more hosts (comma-separated). Optionally provide CK/CS and/or a JWT to probe auth.</p>

			<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
				<?php wp_nonce_field( 'aaa_api_auditor_save' ); ?>
				<input type="hidden" name="action" value="aaa_api_auditor_save" />
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label>Hosts</label></th>
						<td><input name="hosts" type="text" class="regular-text" value="<?php echo esc_attr( $opts['hosts'] ?? '' ); ?>" placeholder="https://example.com, https://site2.tld"></td>
					</tr>
					<tr>
						<th scope="row"><label>Consumer Key (CK)</label></th>
						<td><input name="ck" type="text" class="regular-text" value="<?php echo esc_attr( $opts['ck'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label>Consumer Secret (CS)</label></th>
						<td><input name="cs" type="text" class="regular-text" value="<?php echo esc_attr( $opts['cs'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label>JWT (optional)</label></th>
						<td><input name="jwt" type="text" class="regular-text" value="<?php echo esc_attr( $opts['jwt'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label>HTTP Timeout (sec)</label></th>
						<td><input name="timeout" type="number" min="3" class="small-text" value="<?php echo esc_attr( $opts['timeout'] ?? 12 ); ?>"></td>
					</tr>
				</table>
				<?php submit_button( 'Save Settings' ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="margin-top:12px">
				<?php wp_nonce_field( 'aaa_api_auditor_scan' ); ?>
				<input type="hidden" name="action" value="aaa_api_auditor_scan" />
				<?php submit_button( 'Run Scan', 'primary', 'submit', false ); ?>
			</form>

			<?php if ( $report ) : ?>
				<h2 style="margin-top:24px">Scan Report</h2>
				<?php foreach ( $report as $hostRow ) : ?>
					<h3><?php echo esc_html( $hostRow['host'] ); ?></h3>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Endpoint</th>
								<th>Public</th>
								<th>CK/CS (Query)</th>
								<th>Basic</th>
								<th>JWT</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $hostRow['endpoints'] as $key => $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['label'] ); ?></td>
								<td><?php echo esc_html( intval( $row['public']['code'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( intval( $row['ckcs_query']['code'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( intval( $row['basic']['code'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( intval( $row['jwt']['code'] ?? 0 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">Tip: 200/201 = OK, 401 = auth required/invalid, 403 = blocked, 404 = missing.</p>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
